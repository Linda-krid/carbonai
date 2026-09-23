<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\FormulaireConfiguration;
use App\Models\FormulaireGenere;
use App\Models\ResultatCarbone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCalculationNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_recent_activity_links_open_calculation_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $entreprise = Entreprise::create([
            'user_id' => $user->id,
            'nom' => 'Entreprise Test',
            'secteur_activite' => 'Industrie',
            'pays' => 'Tunisie',
            'ville' => 'Tunis',
            'nombre_employes' => 42,
            'type_production' => 'Production',
            'annee_calcul' => 2026,
        ]);
        $configuration = FormulaireConfiguration::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'postes_emission' => ['production'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['sections' => [['key' => 'production', 'title' => 'Production']]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $resultat = ResultatCarbone::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_genere_id' => $formulaire->id,
            'total_kg_co2e' => 125000,
            'total_t_co2e_an' => 125,
            'detail_emissions' => [
                [
                    'categorie' => 'production',
                    'label' => 'Production',
                    'facteur_nom' => 'Four industriel',
                    'facteur_source' => 'Base CarbonAI',
                    'valeur' => 50,
                    'valeur_annualisee' => 50,
                    'unite' => 'tonne',
                    'periode' => 'annee',
                    'coefficient' => 1400,
                    'emissions_kgco2e' => 70000,
                    'emissions_tco2e' => 70,
                ],
            ],
            'facteurs_eleves' => [],
            'statut' => 'calcule',
            'calculated_at' => now(),
        ]);

        $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSeeText('Calculé');
        $dashboardResponse->assertDontSeeText('Calcule');
        $dashboardResponse->assertSee(route('admin.calculs.index'), false);
        $dashboardResponse->assertSee(route('admin.calculs.show', $resultat), false);

        $indexResponse = $this->actingAs($admin)->get(route('admin.calculs.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSeeText('Entreprise Test');
        $indexResponse->assertSeeText('Calculé');
        $indexResponse->assertSeeText('Voir détails');

        $showResponse = $this->actingAs($admin)->get(route('admin.calculs.show', $resultat));

        $showResponse->assertOk();
        $showResponse->assertSeeText('Détail du calcul carbone');
        $showResponse->assertSeeText('Four industriel');
        $showResponse->assertSeeText('Base CarbonAI');
    }
}
