<?php

namespace App\Http\Controllers;

use App\Models\Recommandation;
use App\Models\ResultatCarbone;
use App\Services\RecommendationGenerationService;
use App\Services\SimplePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(): View
    {
        return view('user.stage-index', [
            'layout' => 'banner',
            'step' => 'Étape 3 — Recommandations',
            'title' => 'Recommandations',
            'subtitle' => 'Les recommandations seront disponibles après le calcul de l’empreinte carbone.',
            'progressStep' => 3,
        ]);
    }

    public function show(ResultatCarbone $resultatCarbone): View
    {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $resultatCarbone->load('entreprise');

        return view('user.recommendations', [
            'resultat' => $resultatCarbone,
            'recommandations' => $resultatCarbone->recommandations()->latest()->get(),
            'suggestionsIa' => $resultatCarbone->suggestionIas()->latest()->get(),
            'recommendationThresholdExceeded' => RecommendationGenerationService::hasRecommendationTrigger($resultatCarbone->details_calcul),
            'recommendationThresholdTco2e' => RecommendationGenerationService::MEDIUM_THRESHOLD_TCO2E,
        ]);
    }

    public function generate(
        Request $request,
        ResultatCarbone $resultatCarbone,
        RecommendationGenerationService $recommendationGenerationService
    ): RedirectResponse
    {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $generated = $recommendationGenerationService->generateForResult($resultatCarbone);

        if (! $generated['success']) {
            $response = data_get($generated, 'response', $generated);

            Log::warning('Recommandations n8n invalides', [
                'resultat_id' => $resultatCarbone->id,
                'response' => $response,
                'erreurs' => data_get($response, 'erreurs', data_get($response, 'verification.erreurs', data_get($response, 'errors', []))),
            ]);

            return back(302, [], route('resultats.show', $resultatCarbone))
                ->with('warning', 'Les recommandations n’ont pas pu être générées correctement.')
                ->with('show_regenerate_recommendations_button', true);
        }

        return redirect()
            ->route('recommandations.show', $resultatCarbone)
            ->with('success', 'Recommandations générées avec succès.');
    }

    public function download(ResultatCarbone $resultatCarbone, SimplePdfService $pdf): Response
    {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $resultatCarbone->loadMissing('entreprise');

        return $pdf->download(
            'recommandations-'.$resultatCarbone->id.'.pdf',
            'Recommandations carbone',
            $this->recommendationPdfSections($resultatCarbone)
        );
    }

    private function recommendationPdfSections(ResultatCarbone $resultatCarbone): array
    {
        $entreprise = $resultatCarbone->entreprise;
        $suggestion = $resultatCarbone->suggestionIas()->latest()->first();
        $recommandations = $resultatCarbone->recommandations()->latest()->get();

        $sections = [
            [
                'heading' => 'Informations entreprise',
                'lines' => [
                    'Entreprise : '.($entreprise?->nom ?? 'Non renseignee'),
                    'Secteur : '.($entreprise?->secteur_activite ?? 'Non renseigne'),
                    'Pays : '.($entreprise?->pays ?? 'Non renseigne'),
                    'Ville : '.($entreprise?->ville ?? 'Non renseignee'),
                    'Annee du calcul : '.($entreprise?->annee_calcul ?? 'Non renseignee'),
                    'Total : '.number_format((float) $resultatCarbone->total_t_co2e_an, 3, ',', ' ').' tCO2e/an',
                ],
            ],
        ];

        if ($suggestion) {
            $sections[] = [
                'heading' => 'Suggestion globale',
                'lines' => [
                    'Source : '.($suggestion->source_emission ?? 'Poste prioritaire'),
                    'Priorite : '.ucfirst((string) $suggestion->priorite),
                    'Impact estime : '.($suggestion->impact_carbone_estime ? $suggestion->impact_carbone_estime.' kgCO2e' : 'A estimer'),
                    (string) $suggestion->contenu,
                ],
            ];
        }

        if ($recommandations->isEmpty()) {
            $sections[] = [
                'heading' => 'Recommandations',
                'lines' => ['Aucune recommandation generee pour ce resultat.'],
            ];

            return $sections;
        }

        $sections[] = [
            'heading' => 'Recommandations',
            'lines' => $recommandations
                ->values()
                ->map(fn (Recommandation $item, int $index) => implode("\n", [
                    ($index + 1).'. '.($item->source_emission ?: 'Poste prioritaire'),
                    'Priorite : '.ucfirst((string) $item->priorite),
                    'Probleme : '.$item->probleme_detecte,
                    'Action : '.$item->action_proposee,
                    'Impact estime : '.($item->impact_carbone_estime ? $item->impact_carbone_estime.' kgCO2e' : 'A estimer'),
                ]))
                ->all(),
        ];

        return $sections;
    }
}
