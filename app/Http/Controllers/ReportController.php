<?php

namespace App\Http\Controllers;

use App\Models\ResultatCarbone;
use App\Services\RecommendationGenerationService;
use App\Services\ReportGenerationService;
use App\Services\SimplePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('user.stage-index', [
            'layout' => 'banner',
            'step' => 'Étape 2 — Rapport',
            'title' => 'Rapport carbone',
            'subtitle' => 'Le rapport carbone sera disponible après le calcul de l’empreinte carbone.',
            'progressStep' => 2,
        ]);
    }

    public function show(ResultatCarbone $resultatCarbone): View
    {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $resultatCarbone->load('entreprise');

        return view('user.report', [
            'resultat' => $resultatCarbone,
            'rapport' => $resultatCarbone->rapports()->latest()->first(),
            'hasRecommendations' => $resultatCarbone->recommandations()->exists(),
            'recommendationThresholdExceeded' => RecommendationGenerationService::hasRecommendationTrigger($resultatCarbone->details_calcul),
            'recommendationThresholdTco2e' => RecommendationGenerationService::MEDIUM_THRESHOLD_TCO2E,
        ]);
    }

    public function generate(ResultatCarbone $resultatCarbone, ReportGenerationService $reportGenerationService): RedirectResponse
    {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $generated = $reportGenerationService->generateForResult($resultatCarbone);

        if (! $generated['success']) {
            $response = data_get($generated, 'response', $generated);

            Log::warning('Rapport n8n invalide', [
                'resultat_id' => $resultatCarbone->id,
                'response' => $response,
                'erreurs' => data_get($response, 'erreurs', data_get($response, 'verification.erreurs', data_get($response, 'errors', []))),
            ]);

            return back(302, [], route('resultats.show', $resultatCarbone))
                ->with('warning', 'Le rapport n’a pas pu être généré correctement.')
                ->with('show_regenerate_report_button', true);
        }

        return redirect()
            ->route('rapports.show', $resultatCarbone)
            ->with('success', 'Rapport généré avec succès.');
    }

    public function download(ResultatCarbone $resultatCarbone, SimplePdfService $pdf): Response
    {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $resultatCarbone->loadMissing('entreprise');
        $rapport = $resultatCarbone->rapports()->latest()->first();

        return $pdf->download(
            'rapport-carbone-'.$resultatCarbone->id.'.pdf',
            $rapport?->titre ?? 'Rapport carbone',
            $this->reportPdfSections($resultatCarbone, $rapport)
        );
    }

    private function reportPdfSections(ResultatCarbone $resultatCarbone, mixed $rapport): array
    {
        $entreprise = $resultatCarbone->entreprise;
        $contenu = (array) ($rapport?->contenu_json ?? []);

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

        if (! $rapport) {
            $sections[] = [
                'heading' => 'Rapport',
                'lines' => ['Aucun rapport genere pour ce resultat.'],
            ];

            return $sections;
        }

        $sections[] = [
            'heading' => 'Resume executif',
            'lines' => $this->pdfLines(data_get($contenu, 'resume_executif', $rapport->resume ?? 'Resume non disponible.')),
        ];
        $sections[] = [
            'heading' => 'Analyse globale',
            'lines' => $this->pdfLines(data_get($contenu, 'analyse_globale', 'Analyse globale non disponible.')),
        ];
        $sections[] = [
            'heading' => 'Analyse par categorie',
            'lines' => $this->pdfLines(data_get($contenu, 'analyse_par_categorie', []), 'Aucune analyse par categorie disponible.'),
        ];
        $sections[] = [
            'heading' => 'Points critiques',
            'lines' => $this->pdfLines(data_get($contenu, 'points_critiques', []), 'Aucun point critique renseigne.'),
        ];
        $sections[] = [
            'heading' => 'Recommandations generales',
            'lines' => $this->pdfLines(data_get($contenu, 'recommandations_generales', []), 'Aucune recommandation generale renseignee.'),
        ];
        $sections[] = [
            'heading' => 'Conclusion',
            'lines' => $this->pdfLines(data_get($contenu, 'conclusion', 'Conclusion non disponible.')),
        ];

        return $sections;
    }

    private function pdfLines(mixed $value, string $empty = 'Non disponible.'): array
    {
        if ($value === null || $value === '') {
            return [$empty];
        }

        if (is_string($value)) {
            return [trim($value) ?: $empty];
        }

        if (is_array($value)) {
            if ($value === []) {
                return [$empty];
            }

            if (array_is_list($value)) {
                return collect($value)
                    ->map(fn (mixed $item, int $index) => ($index + 1).'. '.$this->stringifyPdfValue($item))
                    ->all();
            }

            return [$this->stringifyPdfValue($value)];
        }

        return [(string) $value];
    }

    private function stringifyPdfValue(mixed $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        return collect($value)
            ->map(function (mixed $item, string|int $key) {
                $label = ucfirst(str_replace('_', ' ', (string) $key));
                $content = is_array($item)
                    ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : (string) $item;

                return $label.' : '.$content;
            })
            ->implode(' | ');
    }
}
