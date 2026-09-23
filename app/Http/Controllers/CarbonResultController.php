<?php

namespace App\Http\Controllers;

use App\Models\ResultatCarbone;
use App\Services\RecommendationGenerationService;
use App\Services\ReportGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CarbonResultController extends Controller
{
    public function index(): View
    {
        $resultat = ResultatCarbone::query()
            ->where('user_id', auth()->id())
            ->with('entreprise', 'formulaireGenere')
            ->withCount('recommandations')
            ->latest()
            ->first();

        if (! $resultat) {
            return view('user.results-index');
        }

        return view('user.results', [
            'resultat' => $resultat,
            'rapport' => $resultat->rapports()->latest()->first(),
        ]);
    }

    public function show(ResultatCarbone $resultatCarbone): View
    {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $resultatCarbone->load('entreprise', 'formulaireGenere')->loadCount('recommandations');

        return view('user.results', [
            'resultat' => $resultatCarbone,
            'rapport' => $resultatCarbone->rapports()->latest()->first(),
        ]);
    }

    public function generateReport(
        ResultatCarbone $resultatCarbone,
        ReportGenerationService $reportGenerationService
    ): RedirectResponse {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $generated = $reportGenerationService->generateForResult($resultatCarbone);

        if (! $generated['success']) {
            $this->logGenerationFailure('Rapport n8n invalide', $resultatCarbone, $generated);

            return back(302, [], route('resultats.show', $resultatCarbone))
                ->with('warning', 'Le rapport n’a pas pu être généré correctement.')
                ->with('show_regenerate_report_button', true);
        }

        return redirect()
            ->route('rapports.show', $resultatCarbone)
            ->with('success', 'Rapport généré avec succès.');
    }

    public function generateRecommendations(
        Request $request,
        ResultatCarbone $resultatCarbone,
        RecommendationGenerationService $recommendationGenerationService
    ): RedirectResponse {
        abort_unless(auth()->id() === $resultatCarbone->user_id, 403);

        $generated = $recommendationGenerationService->generateForResult($resultatCarbone);

        if (! $generated['success']) {
            $this->logGenerationFailure('Recommandations n8n invalides', $resultatCarbone, $generated);

            return $this->redirectAfterRecommendationFailure($request, $resultatCarbone)
                ->with('warning', 'Les recommandations n’ont pas pu être générées correctement.')
                ->with('show_regenerate_recommendations_button', true);
        }

        return redirect()
            ->route('recommandations.show', $resultatCarbone)
            ->with('success', 'Recommandations générées avec succès.');
    }

    private function redirectAfterRecommendationFailure(Request $request, ResultatCarbone $resultatCarbone): RedirectResponse
    {
        return back(302, [], route('resultats.show', $resultatCarbone));
    }

    private function logGenerationFailure(string $message, ResultatCarbone $resultatCarbone, array $generated): void
    {
        $response = data_get($generated, 'response', $generated);

        Log::warning($message, [
            'resultat_id' => $resultatCarbone->id,
            'response' => $response,
            'erreurs' => data_get($response, 'erreurs', data_get($response, 'verification.erreurs', data_get($response, 'errors', []))),
        ]);
    }
}
