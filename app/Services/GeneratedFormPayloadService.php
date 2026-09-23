<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\FacteurEmission;
use App\Models\FormulaireConfiguration;
use App\Support\CarbonCategory;

class GeneratedFormPayloadService
{
    public function selectedCategories(iterable $categories): array
    {
        return CarbonCategory::normalizeMany($categories);
    }

    public function build(Entreprise $entreprise, FormulaireConfiguration $configuration, iterable $categories): array
    {
        $selectedCategories = $this->selectedCategories($categories);
        $availableFactors = $this->availableFactorsByCategory();
        $selectedAvailableFactorCount = collect($selectedCategories)
            ->sum(fn (string $category) => count($availableFactors[$category] ?? []));

        return [
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'entreprise' => [
                'nom' => $entreprise->nom,
                'secteur' => $entreprise->secteur_activite,
                'pays' => $entreprise->pays,
                'ville' => $entreprise->ville,
                'nombre_employes' => $entreprise->nombre_employes,
                'type_production' => $entreprise->type_production,
                'annee_calcul' => $entreprise->annee_calcul,
            ],
            'categories_selectionnees' => $selectedCategories,
            'facteurs_selectionnes' => $selectedCategories,
            'postes_emission_selectionnes' => $selectedCategories,
            'nombre_categories_selectionnees' => count($selectedCategories),
            'nombre_sections_attendues' => count($selectedCategories),
            'nombre_facteurs_disponibles_selectionnes' => $selectedAvailableFactorCount,
            'facteurs_disponibles' => $availableFactors,
        ];
    }

    public function availableFactorsByCategory(): array
    {
        return FacteurEmission::query()
            ->where('actif', true)
            ->orderBy('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy('categorie')
            ->map(fn ($items) => $items
                ->map(fn (FacteurEmission $factor) => [
                    'id' => $factor->id,
                    'nom' => $factor->nom,
                    'categorie' => $factor->categorie,
                    'unite' => $factor->unite,
                    'coefficient' => (float) $factor->coefficient,
                ])
                ->values()
                ->all())
            ->all();
    }
}
