<?php

namespace App\Services;

use App\Models\Recommandation;
use App\Models\ResultatCarbone;
use App\Models\SuggestionIa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecommendationGenerationService
{
    public const MEDIUM_THRESHOLD_TCO2E = 10;
    public const HIGH_THRESHOLD_TCO2E = 50;

    public function __construct(private readonly N8nService $n8nService)
    {
    }

    public function generateForResult(ResultatCarbone $resultatCarbone): array
    {
        $resultatCarbone->loadMissing('entreprise');

        $payload = $this->payload($resultatCarbone);
        Log::info('Payload recommandations envoyé à n8n', $payload);

        $generated = $this->n8nService->generateRecommendations($payload);
        Log::info('Réponse recommandations n8n', $generated);

        if (! ($generated['success'] ?? false)) {
            return [
                'success' => false,
                'message' => (string) ($generated['message'] ?? 'Les recommandations n’ont pas pu être générées.'),
                'response' => $generated,
                'suggestion' => null,
                'recommandations' => collect(),
            ];
        }

        $suggestionPayload = (array) (
            data_get($generated, 'suggestion_recommandations.suggestion')
            ?? data_get($generated, 'suggestion')
            ?? data_get($generated, 'suggestion_ia')
            ?? []
        );
        $recommendationPayloads = collect(
            data_get(
                $generated,
                'suggestion_recommandations.recommandations',
                data_get($generated, 'recommandations', data_get($generated, 'recommendations', []))
            )
        )
            ->filter(fn ($item) => is_array($item))
            ->values();

        if ($suggestionPayload === [] && $recommendationPayloads->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Réponse n8n invalide : aucune recommandation reçue.',
                'response' => $generated,
                'suggestion' => null,
                'recommandations' => collect(),
            ];
        }

        $suggestion = $this->storeSuggestion($resultatCarbone, $suggestionPayload, $generated);
        $recommendations = $recommendationPayloads
            ->map(fn (array $item) => $this->storeRecommendation($resultatCarbone, $item))
            ->filter()
            ->values();

        return [
            'success' => true,
            'message' => (string) ($generated['message'] ?? 'Recommandations générées avec succès'),
            'response' => $generated,
            'suggestion' => $suggestion,
            'recommandations' => $recommendations,
        ];
    }

    public function payload(ResultatCarbone $resultatCarbone): array
    {
        $details = array_values($resultatCarbone->detail_emissions ?? []);
        $dominant = collect($details)->sortByDesc(fn (array $detail) => (float) ($detail['emissions_kgco2e'] ?? 0))->first();
        $entreprise = $resultatCarbone->entreprise;

        return [
            'entreprise_id' => $resultatCarbone->entreprise_id,
            'resultat_id' => $resultatCarbone->id,
            'resultat_carbone_id' => $resultatCarbone->id,
            'entreprise' => [
                'nom' => $entreprise?->nom,
                'secteur' => $entreprise?->secteur_activite,
                'pays' => $entreprise?->pays,
                'ville' => $entreprise?->ville,
                'nombre_employes' => $entreprise?->nombre_employes,
                'type_production' => $entreprise?->type_production,
                'annee_calcul' => $entreprise?->annee_calcul,
            ],
            'resultat_carbone' => [
                'total_kgco2e' => (float) $resultatCarbone->total_kg_co2e,
                'total_tco2e' => (float) $resultatCarbone->total_t_co2e_an,
                'categorie_dominante' => $dominant['label'] ?? $dominant['categorie'] ?? null,
                'facteur_dominant' => $dominant['facteur_nom'] ?? null,
                'statut_calcul' => $resultatCarbone->statut,
            ],
            'facteurs_eleves' => $this->highEmissionFactors($details),
            'details_resultat' => $details,
            'seuils_carbone' => [
                'seuil_moyen_tco2e' => self::MEDIUM_THRESHOLD_TCO2E,
                'seuil_eleve_tco2e' => self::HIGH_THRESHOLD_TCO2E,
            ],
        ];
    }

    public static function hasRecommendationTrigger(array $details): bool
    {
        return collect($details)
            ->contains(fn (mixed $detail) => is_array($detail)
                && (float) ($detail['emissions_tco2e'] ?? 0) >= self::MEDIUM_THRESHOLD_TCO2E);
    }

    private function highEmissionFactors(array $details): array
    {
        return collect($details)
            ->filter(fn (mixed $detail) => is_array($detail))
            ->map(function (array $detail) {
                $emissionsTco2e = (float) ($detail['emissions_tco2e'] ?? 0);

                return [
                    'categorie' => $detail['categorie'] ?? null,
                    'label' => $detail['label'] ?? $detail['categorie'] ?? null,
                    'facteur_emission_id' => $detail['facteur_emission_id'] ?? null,
                    'facteur_nom' => $detail['facteur_nom'] ?? null,
                    'source_emission' => $detail['facteur_nom'] ?? $detail['label'] ?? $detail['categorie'] ?? 'Poste prioritaire',
                    'emissions_kgco2e' => (float) ($detail['emissions_kgco2e'] ?? 0),
                    'emissions_tco2e' => $emissionsTco2e,
                    'niveau_seuil' => $emissionsTco2e >= self::HIGH_THRESHOLD_TCO2E ? 'eleve' : 'moyen',
                ];
            })
            ->filter(fn (array $detail) => $detail['emissions_tco2e'] >= self::MEDIUM_THRESHOLD_TCO2E)
            ->sortByDesc('emissions_tco2e')
            ->values()
            ->all();
    }

    private function storeSuggestion(ResultatCarbone $resultatCarbone, array $suggestionPayload, array $generated): ?SuggestionIa
    {
        if (! Schema::hasTable('suggestion_ias')) {
            return null;
        }

        $content = $suggestionPayload['contenu']
            ?? $suggestionPayload['suggestion']
            ?? $generated['message']
            ?? json_encode($suggestionPayload ?: $generated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $data = $this->onlyExistingColumns('suggestion_ias', [
            'user_id' => $resultatCarbone->user_id,
            'entreprise_id' => $resultatCarbone->entreprise_id,
            'resultat_carbone_id' => $resultatCarbone->id,
            'source_emission' => (string) ($suggestionPayload['source_emission'] ?? data_get($generated, 'suggestion_recommandations.recommandations.0.source_emission', 'Poste prioritaire')),
            'contenu' => is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'priorite' => $this->normalizePriority($suggestionPayload['priorite'] ?? 'moyenne'),
            'impact_carbone_estime' => $this->nullableDecimal($suggestionPayload['impact_carbone_estime'] ?? $suggestionPayload['impact_carbone'] ?? null),
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        if ($data === []) {
            return null;
        }

        $lookup = $this->onlyExistingColumns('suggestion_ias', [
            'resultat_carbone_id' => $resultatCarbone->id,
        ]);

        return $lookup === []
            ? SuggestionIa::create($data)
            : SuggestionIa::updateOrCreate($lookup, $data);
    }

    private function storeRecommendation(ResultatCarbone $resultatCarbone, array $item): ?Recommandation
    {
        if (! Schema::hasTable('recommandations')) {
            return null;
        }

        $data = $this->onlyExistingColumns('recommandations', [
            'user_id' => $resultatCarbone->user_id,
            'entreprise_id' => $resultatCarbone->entreprise_id,
            'resultat_carbone_id' => $resultatCarbone->id,
            'source_emission' => $item['source_emission'] ?? $item['facteur_nom'] ?? $item['categorie'] ?? 'Poste prioritaire',
            'categorie' => $item['categorie'] ?? null,
            'facteur_nom' => $item['facteur_nom'] ?? null,
            'probleme_detecte' => $item['probleme_detecte'] ?? 'Poste prioritaire dans l’empreinte carbone calculée.',
            'action_proposee' => $item['action_proposee'] ?? $item['action'] ?? 'Mettre en place un plan de réduction.',
            'priorite' => $this->normalizePriority($item['priorite'] ?? 'moyenne'),
            'impact_carbone' => $this->nullableDecimal($item['impact_carbone'] ?? $item['impact_carbone_estime'] ?? null),
            'impact_carbone_estime' => $this->nullableDecimal($item['impact_carbone_estime'] ?? $item['impact_carbone'] ?? null),
            'horizon' => $item['horizon'] ?? null,
            'indicateur_suivi' => $item['indicateur_suivi'] ?? null,
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        if ($data === []) {
            return null;
        }

        $lookup = $this->onlyExistingColumns('recommandations', [
            'resultat_carbone_id' => $resultatCarbone->id,
            'source_emission' => $data['source_emission'] ?? 'Poste prioritaire',
            'probleme_detecte' => $data['probleme_detecte'] ?? 'Poste prioritaire dans l’empreinte carbone calculée.',
        ]);

        return $lookup === []
            ? Recommandation::create($data)
            : Recommandation::updateOrCreate($lookup, $data);
    }

    private function normalizePriority(?string $priority): string
    {
        $normalized = Str::of($priority ?? '')
            ->ascii()
            ->lower()
            ->replace([' ', '-'], '_')
            ->trim()
            ->toString();

        return match ($normalized) {
            'faible', 'basse', 'low' => 'faible',
            'elevee', 'eleve', 'haute', 'high', 'urgent', 'critique' => 'elevee',
            default => 'moyenne',
        };
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = str_replace(["\u{00A0}", ' '], '', trim($value));

        if (is_numeric(str_replace(',', '.', $normalized))) {
            return (float) str_replace(',', '.', $normalized);
        }

        if (preg_match('/-?\d+(?:[,.]\d+)?/', $normalized, $matches) === 1) {
            return (float) str_replace(',', '.', $matches[0]);
        }

        return null;
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return collect($data)
            ->filter(fn ($value, string $column) => Schema::hasColumn($table, $column))
            ->all();
    }
}
