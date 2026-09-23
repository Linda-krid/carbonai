<?php

namespace App\Http\Controllers;

use App\Models\DonneeEmpreinte;
use App\Models\FacteurEmission;
use App\Models\FormulaireGenere;
use App\Models\ResultatCarbone;
use App\Services\CarbonCalculationService;
use App\Services\GeneratedFormPayloadService;
use App\Services\GeneratedFormSchemaService;
use App\Services\N8nService;
use App\Support\CarbonCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GeneratedFormController extends Controller
{
    public function index(): View
    {
        return view('user.stage-index', [
            'layout' => 'banner',
            'step' => 'Étape 1 — Formulaire généré',
            'title' => 'Formulaire généré',
            'subtitle' => 'Le formulaire dynamique sera disponible après la configuration du calcul carbone.',
            'progressStep' => 1,
        ]);
    }

    public function show(FormulaireGenere $formulaireGenere, GeneratedFormSchemaService $schemaService): View
    {
        $this->authorizeOwner($formulaireGenere->user_id);

        $formulaireGenere->loadMissing('entreprise', 'configuration');

        $schema = $formulaireGenere->schema_json ?? [];
        $selectedCategories = CarbonCategory::normalizeMany(
            $formulaireGenere->configuration?->postes_emission ?? data_get($schema, 'categories_selectionnees', [])
        );
        $sections = $schemaService->normalizeSections($schema, $selectedCategories);

        $factorsByCategory = FacteurEmission::query()
            ->where('actif', true)
            ->orderBy('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy('categorie');

        return view('user.generated-form', [
            'formulaire' => $formulaireGenere,
            'sections' => $sections,
            'factorsByCategory' => $factorsByCategory,
            'facteursParCategorie' => $factorsByCategory,
            'categories' => config('carbon.emission_posts'),
        ]);
    }

    public function submit(
        Request $request,
        FormulaireGenere $formulaireGenere,
        CarbonCalculationService $calculationService
    ): RedirectResponse {
        return $this->calculate($request, $formulaireGenere, $calculationService);
    }

    public function calculate(
        Request $request,
        FormulaireGenere $formulaireGenere,
        CarbonCalculationService $calculationService
    ): RedirectResponse
    {
        $this->authorizeOwner($formulaireGenere->user_id);
        $formulaireGenere->loadMissing('entreprise');
        \Log::info('Données calcul formulaire', $request->all());

        if (! $request->has('lignes') && $request->has('emissions')) {
            $request->merge(['lignes' => array_values($request->input('emissions', []))]);
        }

        if (is_array($request->input('lignes'))) {
            $request->merge(['lignes' => $this->prepareCalculationLines($request->input('lignes', []))]);
        }

        $allowedCategories = array_keys(config('carbon.emission_posts'));

        $validated = $request->validate([
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.categorie' => ['required', 'string', Rule::in($allowedCategories)],
            'lignes.*.facteur_emission_id' => ['required', 'integer'],
            'lignes.*.champ_calcul' => ['required', 'string', 'max:100'],
            'lignes.*.champs' => ['required', 'array'],
            'lignes.*.champs.*' => ['nullable'],
            'lignes.*.valeur' => ['required', 'numeric', 'min:0'],
            'lignes.*.unite' => ['nullable', 'string', 'max:50'],
            'lignes.*.periode' => ['required', Rule::in(['jour', 'semaine', 'mois', 'trimestre', 'annee'])],
            'lignes.*.jours_declares' => ['nullable', 'numeric', 'min:1'],
            'lignes.*.date_debut' => ['nullable', 'date'],
            'lignes.*.date_fin' => ['nullable', 'date'],
            'lignes.*.justificatif' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ], [
            'lignes.*.champ_calcul.required' => 'Aucun champ de calcul n’est défini pour cette section.',
            'lignes.*.champs.required' => 'Aucun champ de calcul n’est défini pour cette section.',
            'lignes.*.valeur.required' => 'La valeur du champ de calcul est obligatoire.',
            'lignes.*.valeur.numeric' => 'La valeur du champ de calcul doit être numérique.',
        ]);

        $calculation = $calculationService->calculate($validated);

        if (! ($calculation['success'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['calculation' => $calculation['message']]);
        }

        $validated = $this->storeJustificatifs($request, $validated);
        $calculation['details'] = $this->attachJustificatifsToDetails(
            $calculation['details'],
            $validated['lignes'] ?? []
        );
        $calculation['detail_emissions'] = $calculation['details'];

        $donnees = DonneeEmpreinte::create([
            'user_id' => $request->user()->id,
            'entreprise_id' => $formulaireGenere->entreprise_id,
            'formulaire_genere_id' => $formulaireGenere->id,
            'donnees_json' => $validated,
            'emissions_detail_json' => $calculation['details'],
        ]);

        $resultat = ResultatCarbone::create([
            'user_id' => $request->user()->id,
            'entreprise_id' => $formulaireGenere->entreprise_id,
            'formulaire_genere_id' => $formulaireGenere->id,
            'donnees_empreinte_id' => $donnees->id,
            'total_kg_co2e' => $calculation['total_kgco2e'],
            'total_t_co2e_an' => $calculation['total_tco2e'],
            'detail_emissions' => $calculation['details'],
            'facteurs_eleves' => $calculation['facteurs_eleves'],
            'statut' => $calculation['statut'],
            'calculated_at' => now(),
        ]);
        $resultat->setRelation('entreprise', $formulaireGenere->entreprise);

        return redirect()
            ->route('resultats.show', $resultat)
            ->with('success', 'Empreinte carbone calculée avec succès.');
    }

    public function regenerate(
        FormulaireGenere $formulaireGenere,
        N8nService $n8nService,
        GeneratedFormPayloadService $payloadService,
        GeneratedFormSchemaService $schemaService
    ): RedirectResponse {
        $this->authorizeOwner($formulaireGenere->user_id);

        $formulaireGenere->load('entreprise', 'configuration');

        $entreprise = $formulaireGenere->entreprise;
        $configuration = $formulaireGenere->configuration;

        if (! $entreprise || ! $configuration) {
            return redirect()
                ->route('formulaires.show', $formulaireGenere)
                ->with('error', 'Élément à régénérer');
        }

        $selectedCategories = $payloadService->selectedCategories($configuration->postes_emission ?? []);
        $generated = $n8nService->generateForm($payloadService->build($entreprise, $configuration, $selectedCategories));
        $generated = $schemaService->completeSchema($generated, $selectedCategories);

        $success = (bool) ($generated['success'] ?? false);

        $formulaireGenere->update([
            'schema_json' => $generated,
            'statut' => data_get($generated, 'formulaire.statut', $success ? 'genere' : 'a_regenerer'),
            'generated_at' => now(),
        ]);

        return redirect()
            ->route('formulaires.show', $formulaireGenere)
            ->with($success ? 'status' : 'error', (string) ($generated['message'] ?? ($success ? 'Formulaire généré avec succès' : 'Élément à régénérer')));
    }

    private function authorizeOwner(int $ownerId): void
    {
        abort_unless(auth()->id() === $ownerId, 403);
    }

    private function prepareCalculationLines(array $lines): array
    {
        return collect($lines)
            ->map(function ($line) {
                if (! is_array($line)) {
                    return $line;
                }

                $champs = $line['champs'] ?? $line['fields'] ?? [];
                $champs = is_array($champs) ? $champs : [];
                $champCalcul = $line['champ_calcul'] ?? null;

                if ((! is_scalar($champCalcul) || trim((string) $champCalcul) === '') && array_key_exists('valeur', $line)) {
                    $champCalcul = 'valeur';
                    $champs['valeur'] ??= $line['valeur'];
                }

                if (is_scalar($champCalcul) && trim((string) $champCalcul) !== '') {
                    $champCalcul = trim((string) $champCalcul);
                    $line['champ_calcul'] = $champCalcul;

                    if (array_key_exists($champCalcul, $champs)) {
                        $line['valeur'] = $champs[$champCalcul];
                    } elseif (array_key_exists('valeur', $line)) {
                        $champs[$champCalcul] = $line['valeur'];
                    }
                }

                $line['champs'] = $champs;
                unset($line['fields']);

                return $line;
            })
            ->values()
            ->all();
    }

    private function storeJustificatifs(Request $request, array $validated): array
    {
        foreach (array_keys($validated['lignes'] ?? []) as $index) {
            $file = $request->file("lignes.{$index}.justificatif");

            if ($file instanceof UploadedFile && $file->isValid()) {
                $path = Storage::disk('public')->putFile('justificatifs', $file);

                if (is_string($path) && $path !== '') {
                    $validated['lignes'][$index]['justificatif_path'] = $path;
                    $validated['lignes'][$index]['justificatif_nom'] = $file->getClientOriginalName();
                    $validated['lignes'][$index]['champs']['justificatif_path'] = $path;
                    $validated['lignes'][$index]['champs']['justificatif_nom'] = $file->getClientOriginalName();
                }
            }

            unset($validated['lignes'][$index]['justificatif']);
        }

        return $validated;
    }

    private function attachJustificatifsToDetails(array $details, array $lines): array
    {
        return collect($details)
            ->map(function (array $detail, int $fallbackIndex) use ($lines) {
                $lineIndex = $detail['ligne_index'] ?? $fallbackIndex;
                $line = $lines[$lineIndex] ?? null;

                if (! is_array($line) || empty($line['justificatif_path'])) {
                    return $detail;
                }

                $detail['justificatif_path'] = $line['justificatif_path'];
                $detail['justificatif_nom'] = $line['justificatif_nom'] ?? null;
                $detail['donnees_dynamiques']['justificatif_path'] = $line['justificatif_path'];
                $detail['donnees_dynamiques']['justificatif_nom'] = $line['justificatif_nom'] ?? null;

                return $detail;
            })
            ->values()
            ->all();
    }
}
