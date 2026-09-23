<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\FormulaireConfiguration;
use App\Models\FormulaireGenere;
use App\Services\GeneratedFormPayloadService;
use App\Services\GeneratedFormSchemaService;
use App\Services\N8nService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfigurationController extends Controller
{
    public function index(): View
    {
        $entreprise = auth()->user()
            ?->entreprises()
            ->latest('id')
            ->first();

        return view('user.configuration', [
            'posts' => config('carbon.emission_posts'),
            'entrepriseDefaults' => [
                'nom' => $entreprise?->nom ?? '',
                'secteur_activite' => $entreprise?->secteur_activite ?? '',
                'pays' => $entreprise?->pays ?: 'Tunisie',
                'ville' => $entreprise?->ville ?? '',
                'nombre_employes' => '',
                'type_production' => '',
                'annee_calcul' => '',
            ],
        ]);
    }

    public function store(
        Request $request,
        N8nService $n8nService,
        GeneratedFormPayloadService $payloadService,
        GeneratedFormSchemaService $schemaService
    ): RedirectResponse {
        $postKeys = array_keys(config('carbon.emission_posts'));
        $request->merge([
            'pays' => filled($request->input('pays')) ? $request->input('pays') : 'Tunisie',
        ]);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'secteur_activite' => ['required', 'string', 'max:255'],
            'pays' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'nombre_employes' => ['required', 'integer', 'min:1'],
            'type_production' => ['nullable', 'string', 'max:255'],
            'annee_calcul' => ['required', 'integer', 'min:2000', 'max:2040'],
            'postes_emission' => ['required', 'array', 'min:1'],
            'postes_emission.*' => ['required', Rule::in($postKeys)],
        ]);
        $selectedCategories = $payloadService->selectedCategories($validated['postes_emission']);

        $entreprise = Entreprise::create([
            'user_id' => $request->user()->id,
            'nom' => $validated['nom'],
            'secteur_activite' => $validated['secteur_activite'],
            'pays' => $validated['pays'],
            'ville' => $validated['ville'],
            'nombre_employes' => $validated['nombre_employes'],
            'type_production' => $validated['type_production'] ?? null,
            'annee_calcul' => $validated['annee_calcul'],
        ]);

        $configuration = FormulaireConfiguration::create([
            'user_id' => $request->user()->id,
            'entreprise_id' => $entreprise->id,
            'postes_emission' => $selectedCategories,
            'statut' => 'configuration',
        ]);

        $schema = $n8nService->generateForm($payloadService->build($entreprise, $configuration, $selectedCategories));
        $schema = $schemaService->completeSchema($schema, $selectedCategories);

        $success = (bool) ($schema['success'] ?? false);

        $formulaire = FormulaireGenere::create([
            'user_id' => $request->user()->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => $schema,
            'statut' => data_get($schema, 'formulaire.statut', $success ? 'genere' : 'a_regenerer'),
            'generated_at' => now(),
        ]);

        return redirect()
            ->route('formulaires.show', $formulaire)
            ->with($success ? 'status' : 'error', (string) ($schema['message'] ?? ($success ? 'Formulaire généré avec succès' : 'Élément à régénérer')));
    }
}
