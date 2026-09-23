<?php

namespace App\Services;

use App\Models\Rapport;
use App\Models\ResultatCarbone;
use Illuminate\Support\Facades\Log;

class ReportGenerationService
{
    public function __construct(private readonly N8nService $n8nService)
    {
    }

    public function generateForResult(ResultatCarbone $resultatCarbone): array
    {
        $resultatCarbone->loadMissing('entreprise');

        $payload = $this->payload($resultatCarbone);
        Log::info('Payload rapport envoyé à n8n', $payload);

        $generated = $this->n8nService->generateReport($payload);
        Log::info('Réponse rapport n8n', $generated);

        $success = (bool) ($generated['success'] ?? false);
        $rapportPayload = (array) ($generated['rapport'] ?? []);

        if (! $success || $rapportPayload === []) {
            return [
                'success' => false,
                'message' => (string) ($generated['message'] ?? "Le résultat est calculé, mais le rapport n’a pas encore été généré."),
                'response' => $generated,
                'rapport' => null,
            ];
        }

        $contenu = $rapportPayload;

        if (array_key_exists('verification', $generated)) {
            $contenu['verification'] = $generated['verification'];
        }

        $rapport = Rapport::updateOrCreate([
            'resultat_carbone_id' => $resultatCarbone->id,
        ], [
            'titre' => (string) ($rapportPayload['titre'] ?? 'Rapport carbone'),
            'resume' => (string) ($rapportPayload['resume_executif'] ?? $rapportPayload['resume'] ?? ($generated['message'] ?? '')),
            'contenu_json' => $contenu,
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => (string) ($generated['message'] ?? 'Rapport généré avec succès'),
            'response' => $generated,
            'rapport' => $rapport,
        ];
    }

    public function payload(ResultatCarbone $resultatCarbone): array
    {
        $details = $resultatCarbone->detail_emissions ?? [];
        $dominant = collect($resultatCarbone->facteurs_eleves ?? [])->first()
            ?? collect($details)->sortByDesc('emissions_kgco2e')->first();
        $entreprise = $resultatCarbone->entreprise;

        return [
            'resultat_id' => $resultatCarbone->id,
            'resultat_carbone_id' => $resultatCarbone->id,
            'entreprise_id' => $resultatCarbone->entreprise_id,
            'entreprise' => [
                'nom' => $entreprise?->nom,
                'secteur' => $entreprise?->secteur_activite,
                'pays' => $entreprise?->pays,
                'ville' => $entreprise?->ville,
                'nombre_employes' => $entreprise?->nombre_employes,
                'type_production' => $entreprise?->type_production,
                'annee_calcul' => $entreprise?->annee_calcul,
            ],
            'resultats' => [
                'total_kgco2e' => (float) $resultatCarbone->total_kg_co2e,
                'total_kg_co2e' => (float) $resultatCarbone->total_kg_co2e,
                'total_tco2e' => (float) $resultatCarbone->total_t_co2e_an,
                'total_t_co2e_an' => (float) $resultatCarbone->total_t_co2e_an,
                'categorie_dominante' => $dominant['label'] ?? $dominant['categorie'] ?? null,
                'facteur_dominant' => $dominant['facteur_nom'] ?? null,
                'details' => $details,
            ],
            'details_resultat' => $details,
        ];
    }
}
