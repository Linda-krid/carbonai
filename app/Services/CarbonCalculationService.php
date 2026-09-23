<?php

namespace App\Services;

use App\Models\FacteurEmission;
use App\Models\FormuleCalcul;
use Illuminate\Support\Str;

class CarbonCalculationService
{
    private const MISSING_FACTOR_MESSAGE = "Les facteurs d’émission nécessaires ne sont pas encore configurés par l’administrateur.";
    private const MISSING_FORMULA_MESSAGE = 'Aucune formule de calcul active n’est configurée. Contactez un administrateur.';
    private const INVALID_PERIOD_MESSAGE = 'La période de calcul sélectionnée est invalide.';

    public function calculate(array $formData): array
    {
        $entries = array_values($formData['lignes'] ?? $formData['emissions'] ?? []);
        $details = [];
        $totalKg = 0.0;
        $totalT = 0.0;

        $formulas = FormuleCalcul::query()
            ->where('actif', true)
            ->get()
            ->keyBy('categorie');

        if ($formulas->isEmpty()) {
            return $this->failed(self::MISSING_FORMULA_MESSAGE);
        }

        foreach ($entries as $entryIndex => $entry) {
            $categorie = (string) ($entry['categorie'] ?? '');
            $facteurEmissionId = $entry['facteur_emission_id'] ?? null;
            $valeur = (float) ($entry['valeur'] ?? 0);

            if ($categorie === '' || $facteurEmissionId === null || $valeur <= 0) {
                continue;
            }

            $factor = FacteurEmission::query()
                ->whereKey($facteurEmissionId)
                ->where('categorie', $categorie)
                ->where('actif', true)
                ->first();

            if (! $factor) {
                return $this->failed(self::MISSING_FACTOR_MESSAGE, [config("carbon.emission_posts.$categorie.label", ucfirst($categorie))]);
            }

            $periodKey = $this->periodKey($entry['periode'] ?? 'annee');

            if (! $periodKey) {
                return $this->failed(self::INVALID_PERIOD_MESSAGE);
            }

            $annualizedValue = $this->annualizeValue($periodKey, $valeur, $entry);
            $coefficient = (float) $factor->coefficient;
            $kgCo2e = round($annualizedValue * $coefficient, 3);
            $tCo2e = round($kgCo2e / 1000, 3);

            $totalKg += $kgCo2e;
            $totalT += $tCo2e;
            $daysDeclared = $periodKey === 'jour' ? $this->declaredDays($entry) : null;

            $details[] = [
                'ligne_index' => $entryIndex,
                'categorie' => $categorie,
                'label' => config("carbon.emission_posts.$categorie.label", ucfirst($categorie)),
                'facteur_emission_id' => $factor->id,
                'facteur_nom' => $factor->nom,
                'facteur_source' => $factor->source,
                'facteur_description' => $factor->description,
                'valeur' => $valeur,
                'valeur_annualisee' => $annualizedValue,
                'unite' => $factor->unite,
                'periode' => $periodKey,
                'jours_declares' => $daysDeclared,
                'date_debut' => $entry['date_debut'] ?? null,
                'date_fin' => $entry['date_fin'] ?? null,
                'justificatif_path' => $entry['justificatif_path'] ?? null,
                'justificatif_nom' => $entry['justificatif_nom'] ?? null,
                'champ_calcul' => $entry['champ_calcul'] ?? null,
                'donnees_dynamiques' => $entry['champs'] ?? $entry['fields'] ?? [],
                'coefficient' => $coefficient,
                'emissions_kgco2e' => $kgCo2e,
                'emissions_tco2e' => $tCo2e,
                'formule_utilisee' => $this->formulaSummary($periodKey, $formulas),
                'formules_utilisees' => [
                    'annualisation' => $this->formulaText($formulas, "annualisation_{$periodKey}", $this->defaultAnnualizationFormula($periodKey)),
                    'calcul_emissions_kgco2e' => $this->formulaText($formulas, 'calcul_emissions_kgco2e', 'valeur_annualisee * coefficient'),
                    'conversion_kgco2e_vers_tco2e' => $this->formulaText($formulas, 'conversion_kgco2e_vers_tco2e', 'emissions_kgco2e / 1000'),
                ],
            ];
        }

        if ($details === []) {
            return $this->failed(self::MISSING_FACTOR_MESSAGE);
        }

        $totalKg = round($totalKg, 3);
        $totalT = round($totalT, 3);
        $detailsByCategory = $this->detailsBy($details, 'categorie');
        $detailsByFactor = $this->detailsBy($details, 'facteur_emission_id');
        $dominant = $this->dominantDetail($details);

        return [
            'success' => true,
            'total_kgco2e' => $totalKg,
            'total_tco2e' => $totalT,
            'total_kg_co2e' => $totalKg,
            'total_t_co2e_an' => $totalT,
            'details' => $details,
            'detail_emissions' => $details,
            'details_par_categorie' => $detailsByCategory,
            'details_par_facteur' => $detailsByFactor,
            'categorie_dominante' => $dominant['categorie'] ?? null,
            'facteur_dominant' => $dominant['facteur_nom'] ?? null,
            'facteurs_eleves' => $this->highestEmissionPosts($details),
            'statut' => 'calcule',
        ];
    }

    private function failed(string $message, array $missing = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'missing' => array_values(array_unique($missing)),
        ];
    }

    private function annualizeValue(string $periodKey, float $value, array $entry): float
    {
        $multiplier = match ($periodKey) {
            'jour' => $this->declaredDays($entry),
            'semaine' => 52,
            'mois' => 12,
            'trimestre' => 4,
            'annee' => 1,
        };

        return round($value * $multiplier, 3);
    }

    private function periodKey(?string $period): ?string
    {
        $normalized = Str::of($period ?? '')
            ->ascii()
            ->lower()
            ->replace([' ', '-'], '_')
            ->trim()
            ->toString();

        return match ($normalized) {
            'jour', 'journalier', 'journaliere', 'quotidien', 'quotidienne' => 'jour',
            'semaine', 'hebdomadaire' => 'semaine',
            'mois', 'mensuel', 'mensuelle' => 'mois',
            'trimestre', 'trimestriel', 'trimestrielle' => 'trimestre',
            'annee', 'annuel', 'annuelle', '' => 'annee',
            default => null,
        };
    }

    private function declaredDays(array $entry): float
    {
        $days = (float) ($entry['jours_declares'] ?? 0);

        return $days > 0 ? $days : 365.0;
    }

    private function highestEmissionPosts(array $details): array
    {
        $maxKg = collect($details)->max('emissions_kgco2e');

        if (! $maxKg || $maxKg <= 0) {
            return [];
        }

        return collect($details)
            ->filter(fn (array $detail) => (float) $detail['emissions_kgco2e'] === (float) $maxKg)
            ->values()
            ->all();
    }

    private function dominantDetail(array $details): ?array
    {
        return collect($details)->sortByDesc('emissions_kgco2e')->first();
    }

    private function detailsBy(array $details, string $key): array
    {
        return collect($details)
            ->groupBy($key)
            ->map(fn ($items, $groupKey) => [
                $key => $groupKey,
                'label' => $items->first()['label'] ?? $groupKey,
                'facteur_nom' => $items->first()['facteur_nom'] ?? null,
                'total_kgco2e' => round((float) $items->sum('emissions_kgco2e'), 3),
                'total_tco2e' => round((float) $items->sum('emissions_tco2e'), 3),
                'details' => $items->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function formulaSummary(string $periodKey, $formulas): string
    {
        $annualization = $this->formulaText($formulas, "annualisation_{$periodKey}", $this->defaultAnnualizationFormula($periodKey));

        return "{$annualization}; emissions_kgco2e = valeur_annualisee * coefficient; emissions_tco2e = emissions_kgco2e / 1000";
    }

    private function formulaText($formulas, string $category, string $fallback): string
    {
        $formula = $formulas->get($category)?->formule;

        return is_string($formula) && trim($formula) !== '' ? $formula : $fallback;
    }

    private function defaultAnnualizationFormula(string $periodKey): string
    {
        return match ($periodKey) {
            'jour' => 'valeur * (jours_declares ou 365)',
            'semaine' => 'valeur * 52',
            'mois' => 'valeur * 12',
            'trimestre' => 'valeur * 4',
            'annee' => 'valeur',
        };
    }
}
