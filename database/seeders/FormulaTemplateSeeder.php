<?php

namespace Database\Seeders;

use App\Models\FormuleCalcul;
use Illuminate\Database\Seeder;

class FormulaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'annualisation_jour' => [
                'nom' => 'Annualisation jour',
                'unite_entree' => 'jour',
                'formule' => 'valeur * 365',
                'description' => 'Convertit une valeur quotidienne en valeur annuelle.',
            ],
            'annualisation_semaine' => [
                'nom' => 'Annualisation semaine',
                'unite_entree' => 'semaine',
                'formule' => 'valeur * 52',
                'description' => 'Convertit une valeur hebdomadaire en valeur annuelle.',
            ],
            'annualisation_mois' => [
                'nom' => 'Annualisation mois',
                'unite_entree' => 'mois',
                'formule' => 'valeur * 12',
                'description' => 'Convertit une valeur mensuelle en valeur annuelle.',
            ],
            'annualisation_trimestre' => [
                'nom' => 'Annualisation trimestre',
                'unite_entree' => 'trimestre',
                'formule' => 'valeur * 4',
                'description' => 'Convertit une valeur trimestrielle en valeur annuelle.',
            ],
            'annualisation_annee' => [
                'nom' => 'Annualisation année',
                'unite_entree' => 'annee',
                'formule' => 'valeur',
                'description' => 'Conserve une valeur déjà annuelle.',
            ],
            'calcul_emissions_kgco2e' => [
                'nom' => 'Calcul émissions kgCO2e',
                'unite_entree' => null,
                'formule' => 'valeur_annualisee * facteur_emission',
                'description' => 'Calcule les émissions en kgCO2e à partir de la valeur annualisée et du facteur d’émission.',
            ],
            'conversion_kgco2e_vers_tco2e' => [
                'nom' => 'Conversion kgCO2e vers tCO2e',
                'unite_entree' => 'kgCO2e',
                'formule' => 'emissions_kgco2e / 1000',
                'description' => 'Convertit les émissions kgCO2e en tCO2e.',
            ],
            'total_tco2e_an' => [
                'nom' => 'Total tCO2e annuel',
                'unite_entree' => 'tCO2e',
                'formule' => 'somme_emissions_tco2e',
                'description' => 'Agrège les émissions annuelles en tCO2e.',
            ],
        ];

        foreach ($templates as $categorie => $template) {
            FormuleCalcul::updateOrCreate(
                ['categorie' => $categorie],
                $template + ['actif' => true]
            );
        }
    }
}
