<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\FormulaireConfiguration;
use App\Models\FormulaireGenere;
use App\Models\ResultatCarbone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyStateNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_pages_open_empty_before_generation_or_calculation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('formulaires.index'))
            ->assertOk()
            ->assertSeeText('Étape 1 — Formulaire généré')
            ->assertSeeText('Formulaire généré')
            ->assertDontSeeText('0 élément(s) disponible(s)')
            ->assertDontSeeText('Aucun formulaire généré');

        $this->actingAs($user)
            ->get(route('resultats.index'))
            ->assertOk()
            ->assertSeeText('Résultats carbone')
            ->assertSeeText('Total kgCO2e')
            ->assertSeeText('0,000')
            ->assertSeeText('Production 0%')
            ->assertSeeText('0,0 tCO2e')
            ->assertSeeText('Détail du calcul')
            ->assertSeeText('Valeur d’activité')
            ->assertDontSeeText('Valeur saisie')
            ->assertDontSeeText('Étape 2 — Résultats')
            ->assertDontSeeText('Résultats récents')
            ->assertDontSeeText('Aucun résultat disponible');

        $this->actingAs($user)
            ->get(route('rapports.index'))
            ->assertOk()
            ->assertSeeText('Étape 2 — Rapport')
            ->assertSeeText('Rapport carbone')
            ->assertDontSeeText('0 élément(s) disponible(s)')
            ->assertDontSeeText('Aucun rapport disponible');

        $this->actingAs($user)
            ->get(route('recommandations.index'))
            ->assertOk()
            ->assertSeeText('Étape 3 — Recommandations')
            ->assertSeeText('Recommandations')
            ->assertDontSeeText('0 élément(s) disponible(s)')
            ->assertDontSeeText('Aucune recommandation disponible');
    }

    public function test_stage_pages_stay_open_and_list_available_items(): void
    {
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
            'postes_emission' => ['carburant'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['success' => true, 'formulaire' => ['sections' => []]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $resultat = ResultatCarbone::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_genere_id' => $formulaire->id,
            'total_kg_co2e' => 0,
            'total_t_co2e_an' => 0,
            'detail_emissions' => [],
            'facteurs_eleves' => [],
            'statut' => 'calcule',
            'calculated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('formulaires.index'))
            ->assertOk()
            ->assertSeeText('Étape 1 — Formulaire généré')
            ->assertSeeText('Formulaire généré')
            ->assertDontSeeText('Entreprise Test')
            ->assertDontSee(route('formulaires.show', $formulaire), false);

        $this->actingAs($user)
            ->get(route('resultats.index'))
            ->assertOk()
            ->assertSeeText('Résultats carbone')
            ->assertDontSeeText('Étape 2 — Résultats')
            ->assertSeeText('Calcul validé')
            ->assertSeeText('Détail du calcul')
            ->assertDontSeeText('Résultats récents')
            ->assertSee(route('resultats.generate-report', $resultat), false);

        $this->actingAs($user)
            ->get(route('rapports.index'))
            ->assertOk()
            ->assertSeeText('Étape 2 — Rapport')
            ->assertSeeText('Rapport carbone')
            ->assertDontSeeText('Entreprise Test')
            ->assertDontSee(route('rapports.show', $resultat), false);

        $this->actingAs($user)
            ->get(route('recommandations.index'))
            ->assertOk()
            ->assertSeeText('Étape 3 — Recommandations')
            ->assertSeeText('Recommandations')
            ->assertDontSeeText('Entreprise Test')
            ->assertDontSee(route('recommandations.show', $resultat), false);
    }

    public function test_sidebar_links_point_to_stage_pages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('configuration.index'));

        $response->assertDontSee(route('dashboard'), false);
        $response->assertSeeText('CarbonAI');
        $response->assertSeeText('Utilisateur');
        $response->assertSee(route('formulaires.index'), false);
        $response->assertSee(route('resultats.index'), false);
        $response->assertSee(route('rapports.index'), false);
        $response->assertSee(route('recommandations.index'), false);
        $response->assertSeeHtml('data-sidebar-icon="configuration"');
        $response->assertSeeHtml('data-sidebar-icon="formulaire"');
        $response->assertSeeHtml('data-sidebar-icon="resultats"');
        $response->assertSeeHtml('data-sidebar-icon="rapport"');
        $response->assertSeeHtml('data-sidebar-icon="recommandations"');
        $response->assertSeeHtml('data-sidebar-icon="historique"');
        $response->assertSeeHtml('data-sidebar-icon="logout"');
    }

    public function test_dashboard_route_stays_on_configuration_for_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('configuration.index'));
    }
}
