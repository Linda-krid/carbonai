<?php

namespace Tests\Unit;

use App\Models\FacteurEmission;
use App\Models\FormuleCalcul;
use App\Services\CarbonCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarbonCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_expected_message_when_a_required_factor_is_missing(): void
    {
        $this->seedFormulas();

        $service = app(CarbonCalculationService::class);

        $result = $service->calculate([
            'emissions' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => 9999,
                    'valeur' => 10000,
                    'unite' => 'kWh',
                    'periode' => 'mois',
                ],
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Les facteurs d’émission nécessaires ne sont pas encore configurés par l’administrateur.', $result['message']);
    }

    public function test_it_uses_deterministic_php_logic_instead_of_executing_formula_text(): void
    {
        $this->seedFormulas([
            'annualisation_mois' => 'valeur * 999',
            'calcul_emissions_kgco2e' => 'valeur_annualisee * 999999',
            'conversion_kgco2e_vers_tco2e' => 'emissions_kgco2e / 2',
        ]);

        $facteur = FacteurEmission::create([
            'nom' => 'Facteur test',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 1,
            'source' => 'Source test',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $service = app(CarbonCalculationService::class);

        $result = $service->calculate([
            'emissions' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'valeur' => 10,
                    'unite' => 'kWh',
                    'periode' => 'mois',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(120.0, (float) $result['details'][0]['valeur_annualisee']);
        $this->assertSame(120.0, (float) $result['details'][0]['emissions_kgco2e']);
        $this->assertSame(0.12, (float) $result['details'][0]['emissions_tco2e']);
        $this->assertSame('valeur * 999', $result['details'][0]['formules_utilisees']['annualisation']);
    }

    public function test_it_does_not_require_every_internal_formula_key_when_active_formulas_exist(): void
    {
        FormuleCalcul::create([
            'categorie' => 'configuration_generale',
            'nom' => 'Configuration générale',
            'formule' => 'configuration métier',
            'unite_entree' => null,
            'description' => 'Une formule active suffit à confirmer la configuration métier.',
            'actif' => true,
        ]);

        $facteur = FacteurEmission::create([
            'nom' => 'Facteur test',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 1,
            'source' => 'Source test',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $service = app(CarbonCalculationService::class);

        $result = $service->calculate([
            'emissions' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'valeur' => 10,
                    'unite' => 'kWh',
                    'periode' => 'mois',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(120.0, (float) $result['total_kgco2e']);
        $this->assertSame('valeur * 12', $result['details'][0]['formules_utilisees']['annualisation']);
    }

    public function test_it_returns_a_clear_message_when_no_active_formula_exists(): void
    {
        $facteur = FacteurEmission::create([
            'nom' => 'Facteur test',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 1,
            'source' => 'Source test',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $service = app(CarbonCalculationService::class);

        $result = $service->calculate([
            'emissions' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'valeur' => 10,
                    'unite' => 'kWh',
                    'periode' => 'mois',
                ],
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Aucune formule de calcul active n’est configurée. Contactez un administrateur.', $result['message']);
    }

    public function test_it_returns_a_clear_message_when_period_is_unknown(): void
    {
        $this->seedFormulas();

        $facteur = FacteurEmission::create([
            'nom' => 'Facteur test',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 1,
            'source' => 'Source test',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $service = app(CarbonCalculationService::class);

        $result = $service->calculate([
            'emissions' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'valeur' => 10,
                    'unite' => 'kWh',
                    'periode' => 'periode_inconnue',
                ],
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('La période de calcul sélectionnée est invalide.', $result['message']);
    }


    public function test_daily_period_defaults_to_365_days_when_days_are_missing(): void
    {
        $this->seedFormulas();

        $facteur = FacteurEmission::create([
            'nom' => 'Facteur journalier',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 2,
            'source' => 'Source test',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $service = app(CarbonCalculationService::class);

        $result = $service->calculate([
            'emissions' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'valeur' => 10,
                    'unite' => 'kWh',
                    'periode' => 'jour',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(365.0, (float) $result['details'][0]['jours_declares']);
        $this->assertSame(3650.0, (float) $result['details'][0]['valeur_annualisee']);
        $this->assertSame(7300.0, (float) $result['details'][0]['emissions_kgco2e']);
    }

    private function seedFormulas(array $overrides = []): void
    {
        foreach (array_merge([
            'annualisation_jour' => 'valeur * jours_declares',
            'annualisation_semaine' => 'valeur * 52',
            'annualisation_mois' => 'valeur * 12',
            'annualisation_trimestre' => 'valeur * 4',
            'annualisation_annee' => 'valeur',
            'calcul_emissions_kgco2e' => 'valeur_annualisee * facteur_emission',
            'conversion_kgco2e_vers_tco2e' => 'emissions_kgco2e / 1000',
            'total_tco2e_an' => 'somme_emissions_tco2e',
        ], $overrides) as $categorie => $formule) {
            FormuleCalcul::create([
                'categorie' => $categorie,
                'nom' => $categorie,
                'formule' => $formule,
                'unite_entree' => null,
                'description' => $categorie,
                'actif' => true,
            ]);
        }
    }
}
