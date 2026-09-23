<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\FacteurEmission;
use App\Models\FormulaireConfiguration;
use App\Models\FormulaireGenere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FormGenerationPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_form_defaults_country_to_tunisia(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('configuration.index'));

        $response->assertOk();
        $response->assertSeeHtml('name="pays"');
        $response->assertSeeHtml('value="Tunisie"');
        $response->assertSeeHtml('max="2040"');
        $response->assertSeeHtml('data-category-icon="carburant"');
        $response->assertSeeHtml('data-category-icon="machines"');
        $response->assertSeeHtml('data-category-icon="production"');
    }

    public function test_configuration_form_prefills_company_fields_from_registered_account(): void
    {
        $user = User::factory()->create();

        Entreprise::create([
            'user_id' => $user->id,
            'nom' => 'Entreprise Compte',
            'secteur_activite' => 'Textile',
            'pays' => 'Tunisie',
            'ville' => 'Sfax',
            'nombre_employes' => 0,
            'type_production' => null,
            'annee_calcul' => now()->year,
        ]);

        $response = $this->actingAs($user)->get(route('configuration.index'));

        $response->assertOk();
        $response->assertSeeHtml('name="nom"');
        $response->assertSeeHtml('value="Entreprise Compte"');
        $response->assertSeeHtml('value="Textile"');
        $response->assertSeeHtml('value="Tunisie"');
        $response->assertSeeHtml('value="Sfax"');
        $response->assertDontSeeHtml('value="0"');
        $response->assertDontSeeHtml('value="'.now()->year.'"');
    }

    public function test_configuration_store_defaults_missing_country_to_tunisia(): void
    {
        config(['services.n8n.generate_form_url' => 'https://n8n.test/formulaire']);

        Http::fake([
            'https://n8n.test/formulaire' => Http::response([
                'success' => true,
                'statut' => 'formulaire_valide',
                'message' => 'Formulaire généré avec succès',
                'formulaire' => [
                    'statut' => 'genere',
                    'sections' => [
                        ['categorie' => 'carburant', 'titre' => 'Carburant', 'champs' => []],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('configuration.store'), [
            'nom' => 'Entreprise Test',
            'secteur_activite' => 'Industrie',
            'ville' => 'Tunis',
            'nombre_employes' => 10,
            'type_production' => 'Production',
            'annee_calcul' => 2040,
            'postes_emission' => ['carburant'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('entreprises', [
            'user_id' => $user->id,
            'nom' => 'Entreprise Test',
            'pays' => 'Tunisie',
            'annee_calcul' => 2040,
        ]);
    }

    public function test_configuration_store_sends_categories_and_available_factors_to_n8n(): void
    {
        config(['services.n8n.generate_form_url' => 'https://n8n.test/formulaire']);

        FacteurEmission::create([
            'nom' => 'Électricité machines industrielles',
            'categorie' => 'machines',
            'unite' => 'kWh',
            'coefficient' => 0.463,
            'source' => 'Source test',
            'description' => 'Facteur actif',
            'actif' => true,
        ]);
        FacteurEmission::create([
            'nom' => 'Facteur inactif',
            'categorie' => 'machines',
            'unite' => 'kWh',
            'coefficient' => 1,
            'source' => 'Source test',
            'description' => 'Facteur inactif',
            'actif' => false,
        ]);

        Http::fake([
            'https://n8n.test/formulaire' => Http::response([
                'success' => true,
                'statut' => 'formulaire_valide',
                'message' => 'Formulaire généré avec succès',
                'formulaire' => [
                    'statut' => 'genere',
                    'sections' => [
                        ['categorie' => 'carburant', 'titre' => 'Carburant', 'champs' => []],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('configuration.store'), [
            'nom' => 'Entreprise Test',
            'secteur_activite' => 'Industrie',
            'pays' => 'Tunisie',
            'ville' => 'Tunis',
            'nombre_employes' => 10,
            'type_production' => 'Production',
            'annee_calcul' => 2026,
            'postes_emission' => ['carburant', 'machines'],
        ]);

        $response->assertRedirect();

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $payload['categories_selectionnees'] === ['carburant', 'machines']
                && $payload['facteurs_selectionnes'] === ['carburant', 'machines']
                && $payload['postes_emission_selectionnes'] === ['carburant', 'machines']
                && $payload['nombre_categories_selectionnees'] === 2
                && $payload['nombre_sections_attendues'] === 2
                && $payload['nombre_facteurs_disponibles_selectionnes'] === 1
                && data_get($payload, 'facteurs_disponibles.machines.0.nom') === 'Électricité machines industrielles'
                && data_get($payload, 'facteurs_disponibles.machines.0.categorie') === 'machines'
                && data_get($payload, 'facteurs_disponibles.machines.0.unite') === 'kWh'
                && data_get($payload, 'facteurs_disponibles.machines.0.coefficient') === 0.463
                && ! collect(data_get($payload, 'facteurs_disponibles.machines', []))
                    ->contains(fn (array $factor) => $factor['nom'] === 'Facteur inactif');
        });

        $schema = FormulaireGenere::query()->firstOrFail()->schema_json;
        $sectionKeys = collect(data_get($schema, 'formulaire.sections'))->pluck('key')->all();

        $this->assertSame(['carburant', 'machines'], $schema['categories_selectionnees']);
        $this->assertContains('carburant', $sectionKeys);
        $this->assertContains('machines', $sectionKeys);
        $this->assertSame(['machines'], $schema['categories_completees_par_laravel']);
    }

    public function test_regenerate_sends_the_same_stable_category_payload_to_n8n(): void
    {
        config(['services.n8n.generate_form_url' => 'https://n8n.test/formulaire']);

        FacteurEmission::create([
            'nom' => 'Électricité bâtiment',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 0.463,
            'source' => 'Source test',
            'description' => 'Facteur actif',
            'actif' => true,
        ]);

        Http::fake([
            'https://n8n.test/formulaire' => Http::response([
                'success' => true,
                'statut' => 'formulaire_valide',
                'message' => 'Formulaire généré avec succès',
                'formulaire' => [
                    'statut' => 'genere',
                    'sections' => [
                        ['categorie' => 'Énergie', 'titre' => 'Énergie', 'champs' => []],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $entreprise = Entreprise::create([
            'user_id' => $user->id,
            'nom' => 'Entreprise Test',
            'secteur_activite' => 'Industrie',
            'pays' => 'Tunisie',
            'ville' => 'Tunis',
            'nombre_employes' => 10,
            'type_production' => 'Production',
            'annee_calcul' => 2026,
        ]);
        $configuration = FormulaireConfiguration::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'postes_emission' => ['batiment', 'materiaux'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['success' => false],
            'statut' => 'a_regenerer',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('formulaires.regenerate', $formulaire));

        $response->assertRedirect(route('formulaires.show', $formulaire));

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $payload['categories_selectionnees'] === ['batiment', 'materiaux']
                && data_get($payload, 'facteurs_disponibles.batiment.0.categorie') === 'batiment'
                && data_get($payload, 'facteurs_disponibles.batiment.0.nom') === 'Électricité bâtiment';
        });

        $schema = $formulaire->refresh()->schema_json;
        $sectionKeys = collect(data_get($schema, 'formulaire.sections'))->pluck('key')->all();

        $this->assertContains('batiment', $sectionKeys);
        $this->assertContains('materiaux', $sectionKeys);
        $this->assertSame(['materiaux'], $schema['categories_completees_par_laravel']);
    }
}
