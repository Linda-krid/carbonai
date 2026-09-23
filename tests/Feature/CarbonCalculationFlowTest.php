<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\DonneeEmpreinte;
use App\Models\FacteurEmission;
use App\Models\FormulaireConfiguration;
use App\Models\FormulaireGenere;
use App\Models\FormuleCalcul;
use App\Models\Rapport;
use App\Models\Recommandation;
use App\Models\ResultatCarbone;
use App\Models\SuggestionIa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarbonCalculationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_the_footprint_and_creates_the_result_record(): void
    {
        $this->seedFormulas();
        config(['services.n8n.generate_report_url' => 'https://n8n.test/generate-report']);
        Http::fake([
            'https://n8n.test/generate-report' => Http::sequence()
                ->push([
                    'success' => true,
                    'statut' => 'rapport_genere',
                    'message' => 'Rapport généré avec succès',
                    'rapport' => [
                        'titre' => 'Rapport n8n',
                        'resume_executif' => 'Résumé exécutif généré par n8n.',
                        'informations_entreprise' => [],
                        'resultats_principaux' => [],
                        'analyse_globale' => 'Analyse globale générée.',
                        'analyse_par_categorie' => [],
                        'points_critiques' => [],
                        'recommandations_generales' => [],
                        'conclusion' => 'Conclusion générée.',
                    ],
                    'verification' => [
                        'valide' => true,
                        'statut' => 'valide',
                        'erreurs' => [],
                        'message' => 'Rapport valide',
                    ],
                ], 200)
                ->push([
                    'success' => true,
                    'statut' => 'rapport_genere',
                    'message' => 'Rapport régénéré avec succès',
                    'rapport' => [
                        'titre' => 'Rapport n8n régénéré',
                        'resume_executif' => 'Résumé mis à jour par n8n.',
                        'analyse_globale' => 'Analyse mise à jour.',
                        'analyse_par_categorie' => [],
                        'points_critiques' => [],
                        'recommandations_generales' => [],
                        'conclusion' => 'Conclusion mise à jour.',
                    ],
                ], 200),
        ]);
        Storage::fake('public');

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
            'postes_emission' => ['batiment'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['sections' => [['key' => 'batiment', 'title' => 'Bâtiment']]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $facteur = FacteurEmission::create([
            'nom' => 'Électricité bâtiment - réseau Tunisie',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 0.463,
            'source' => 'Source officielle',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $response = $this->actingAs($user)->post(route('formulaires.calculate', $formulaire), [
            'lignes' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'champ_calcul' => 'consommation_electricite',
                    'champs' => [
                        'consommation_electricite' => 10000,
                    ],
                    'periode' => 'mois',
                    'date_debut' => '2026-01-01',
                    'date_fin' => '2026-01-31',
                    'justificatif' => UploadedFile::fake()->create('facture.pdf', 64, 'application/pdf'),
                ],
            ],
        ]);

        $resultat = ResultatCarbone::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('resultats.show', $resultat));
        $response->assertSessionHas('success', 'Empreinte carbone calculée avec succès.');

        $resultsResponse = $this->actingAs($user)->get(route('resultats.show', $resultat));
        $resultsResponse->assertSee('Détail du calcul');
        $resultsResponse->assertSeeText('Résultat vérifié');
        $resultsResponse->assertSeeText('Calcul vérifié');
        $resultsResponse->assertSeeText('Facteurs actifs utilisés');
        $resultsResponse->assertSeeText('Coefficients issus de la base de référence');
        $resultsResponse->assertSeeText('Sources affichées');
        $resultsResponse->assertSeeText('Formules internes appliquées');
        $resultsResponse->assertSeeText('Résultat traçable');
        $resultsResponse->assertSeeText('Valeur d’activité');
        $resultsResponse->assertSeeText('Source du facteur');
        $resultsResponse->assertSeeText('Facteur actif');
        $resultsResponse->assertSeeText('Source officielle');
        $resultsResponse->assertSeeText('sources référencées');
        $resultsResponse->assertSeeText('calcul fiable et traçable');
        $resultsResponse->assertDontSeeText('Valeur saisie');
        $resultsResponse->assertSee('Total tCO2e/an');
        $resultsResponse->assertSee('Générer le rapport');
        $resultsResponse->assertDontSeeText('Générer les recommandations');
        $resultsResponse->assertDontSeeText('Voir les recommandations');

        $this->assertSame('55560.000', $resultat->total_kg_co2e);
        $this->assertSame('55.560', $resultat->total_t_co2e_an);
        $this->assertSame('calcule', $resultat->statut);
        $this->assertSame(1, count($resultat->detail_emissions));
        $this->assertSame($facteur->id, $resultat->detail_emissions[0]['facteur_emission_id']);
        $this->assertSame(120000.0, (float) $resultat->detail_emissions[0]['valeur_annualisee']);
        $this->assertSame(55560.0, (float) $resultat->detail_emissions[0]['emissions_kgco2e']);
        $this->assertSame('2026-01-01', $resultat->detail_emissions[0]['date_debut']);
        $this->assertSame('consommation_electricite', $resultat->detail_emissions[0]['champ_calcul']);
        $this->assertSame(10000, $resultat->detail_emissions[0]['donnees_dynamiques']['consommation_electricite']);
        $this->assertSame('facture.pdf', $resultat->detail_emissions[0]['justificatif_nom']);
        $this->assertStringStartsWith('justificatifs/', $resultat->detail_emissions[0]['justificatif_path']);
        Storage::disk('public')->assertExists($resultat->detail_emissions[0]['justificatif_path']);
        $donnees = DonneeEmpreinte::query()->firstOrFail();
        $this->assertSame($resultat->detail_emissions[0]['justificatif_path'], $donnees->donnees_json['lignes'][0]['justificatif_path']);
        $this->assertSame('facture.pdf', $donnees->donnees_json['lignes'][0]['justificatif_nom']);
        $this->assertNotEmpty($resultat->detail_emissions[0]['formule_utilisee']);
        $this->assertDatabaseCount('rapports', 0);

        $reportResponse = $this->actingAs($user)->post(route('resultats.generate-report', $resultat));

        $reportResponse->assertRedirect(route('rapports.show', $resultat));
        $this->assertDatabaseCount('rapports', 1);
        $rapport = Rapport::query()->firstOrFail();
        $this->assertSame('genere', $rapport->statut);
        $this->assertSame('Rapport n8n', $rapport->titre);
        $this->assertSame('Résumé exécutif généré par n8n.', $rapport->resume);
        $this->assertSame('Rapport valide', $rapport->contenu_json['verification']['message']);

        Http::assertSent(function ($request) use ($resultat, $entreprise, $facteur) {
            $payload = $request->data();

            return $payload['resultat_id'] === $resultat->id
                && $payload['resultat_carbone_id'] === $resultat->id
                && $payload['entreprise_id'] === $entreprise->id
                && data_get($payload, 'entreprise.nom') === 'Entreprise Test'
                && data_get($payload, 'entreprise.secteur') === 'Industrie'
                && data_get($payload, 'resultats.total_kgco2e') === 55560.0
                && data_get($payload, 'resultats.total_tco2e') === 55.56
                && data_get($payload, 'details_resultat.0.facteur_emission_id') === $facteur->id;
        });

        $this->actingAs($user)->post(route('resultats.generate-report', $resultat))
            ->assertRedirect(route('rapports.show', $resultat));

        $this->assertDatabaseCount('resultat_carbones', 1);
        $this->assertDatabaseCount('rapports', 1);
        $rapport->refresh();
        $this->assertSame('Rapport n8n régénéré', $rapport->titre);
        $this->assertSame('Résumé mis à jour par n8n.', $rapport->resume);
    }

    public function test_it_keeps_the_result_when_report_generation_fails(): void
    {
        $this->seedFormulas();
        config(['services.n8n.generate_report_url' => 'https://n8n.test/rapport']);
        Http::fake([
            'https://n8n.test/rapport' => Http::response(['success' => false, 'message' => 'n8n indisponible'], 500),
        ]);

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
            'postes_emission' => ['batiment'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['sections' => [['key' => 'batiment', 'title' => 'Bâtiment']]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $facteur = FacteurEmission::create([
            'nom' => 'Électricité bâtiment - réseau Tunisie',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 0.463,
            'source' => 'Source officielle',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $response = $this->actingAs($user)->post(route('formulaires.calculate', $formulaire), [
            'lignes' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'champ_calcul' => 'consommation_electricite',
                    'champs' => [
                        'consommation_electricite' => 10000,
                    ],
                    'periode' => 'mois',
                ],
            ],
        ]);

        $resultat = ResultatCarbone::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('resultats.show', $resultat));

        $reportResponse = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('resultats.generate-report', $resultat));

        $reportResponse->assertSee("Le rapport n’a pas pu être généré correctement.");
        $reportResponse->assertSee('Régénérer le rapport');
        $reportResponse->assertDontSee('Erreur HTTP n8n : 500');
        $reportResponse->assertDontSee('n8n indisponible');
        $this->assertDatabaseCount('resultat_carbones', 1);
        $this->assertDatabaseCount('rapports', 0);
    }

    public function test_it_generates_recommendations_from_existing_result(): void
    {
        config(['services.n8n.generate_recommendations_url' => 'https://n8n.test/generate-recommendations']);

        Http::fake([
            'https://n8n.test/generate-recommendations' => Http::sequence()
                ->push([
                    'success' => true,
                    'statut' => 'recommandations_valides',
                    'message' => 'Recommandations générées avec succès',
                    'suggestion_recommandations' => [
                        'statut' => 'genere',
                        'suggestion' => [
                            'source_emission' => 'Production',
                            'contenu' => 'Prioriser la ligne de production principale.',
                            'priorite' => 'élevée',
                            'impact_carbone_estime' => 12.5,
                        ],
                        'recommandations' => [
                            [
                                'source_emission' => 'Production',
                                'categorie' => 'production',
                                'facteur_nom' => 'Four industriel',
                                'probleme_detecte' => 'Consommation élevée sur la production.',
                                'action_proposee' => 'Optimiser les cycles de chauffe.',
                                'priorite' => 'élevée',
                                'impact_carbone_estime' => 12.5,
                                'horizon' => '3 mois',
                                'indicateur_suivi' => 'kWh par lot',
                            ],
                            [
                                'source_emission' => 'Carburant',
                                'categorie' => 'carburant',
                                'facteur_nom' => 'Diesel / gasoil',
                                'probleme_detecte' => 'Le carburant est la source la plus importante.',
                                'action_proposee' => 'Réduire la consommation de Diesel / gasoil.',
                                'priorite' => 'elevee',
                                'impact_carbone_estime' => 'réduction potentielle élevée',
                            ],
                        ],
                    ],
                    'verification' => [
                        'valide' => true,
                        'statut' => 'valide',
                        'erreurs' => [],
                    ],
                ], 200)
                ->push([
                    'success' => true,
                    'statut' => 'recommandations_valides',
                    'message' => 'Recommandations régénérées avec succès',
                    'suggestion_recommandations' => [
                        'statut' => 'genere',
                        'suggestion' => [
                            'source_emission' => 'Production',
                            'contenu' => 'Nouvelle synthèse prioritaire.',
                            'priorite' => 'élevée',
                            'impact_carbone_estime' => 20,
                        ],
                        'recommandations' => [
                            [
                                'source_emission' => 'Production',
                                'categorie' => 'production',
                                'facteur_nom' => 'Four industriel',
                                'probleme_detecte' => 'Consommation élevée sur la production.',
                                'action_proposee' => 'Installer un pilotage énergétique plus fin.',
                                'priorite' => 'élevée',
                                'impact_carbone_estime' => 20,
                                'horizon' => '2 mois',
                                'indicateur_suivi' => 'kWh par lot',
                            ],
                            [
                                'source_emission' => 'Carburant',
                                'categorie' => 'carburant',
                                'facteur_nom' => 'Diesel / gasoil',
                                'probleme_detecte' => 'Le carburant est la source la plus importante.',
                                'action_proposee' => 'Planifier une bascule progressive vers des alternatives.',
                                'priorite' => 'elevee',
                                'impact_carbone_estime' => 8,
                            ],
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
            'total_kg_co2e' => 72000,
            'total_t_co2e_an' => 72,
            'detail_emissions' => [
                [
                    'categorie' => 'production',
                    'label' => 'Production',
                    'facteur_emission_id' => 10,
                    'facteur_nom' => 'Four industriel',
                    'emissions_kgco2e' => 70000,
                    'emissions_tco2e' => 70,
                ],
                [
                    'categorie' => 'transport',
                    'label' => 'Transport',
                    'facteur_emission_id' => 11,
                    'facteur_nom' => 'Transport local',
                    'emissions_kgco2e' => 2000,
                    'emissions_tco2e' => 2,
                ],
            ],
            'facteurs_eleves' => [],
            'statut' => 'calcule',
            'calculated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('resultats.generate-recommendations', $resultat));

        $response->assertRedirect(route('recommandations.show', $resultat));
        $this->assertDatabaseCount('suggestion_ias', 1);
        $this->assertDatabaseCount('recommandations', 2);

        $suggestion = SuggestionIa::query()->firstOrFail();
        $recommendation = Recommandation::query()->where('source_emission', 'Production')->firstOrFail();
        $textImpactRecommendation = Recommandation::query()->where('source_emission', 'Carburant')->firstOrFail();

        $this->assertSame($resultat->id, $suggestion->resultat_carbone_id);
        $this->assertSame('Production', $suggestion->source_emission);
        $this->assertSame('elevee', $suggestion->priorite);
        $this->assertSame('Production', $recommendation->source_emission);
        $this->assertSame('elevee', $recommendation->priorite);
        $this->assertSame('12.500', $recommendation->impact_carbone_estime);
        $this->assertNull($textImpactRecommendation->impact_carbone_estime);

        Http::assertSent(function ($request) use ($resultat, $entreprise) {
            $payload = $request->data();

            return $payload['entreprise_id'] === $entreprise->id
                && $payload['resultat_id'] === $resultat->id
                && $payload['resultat_carbone_id'] === $resultat->id
                && data_get($payload, 'entreprise.secteur') === 'Industrie'
                && data_get($payload, 'resultat_carbone.total_kgco2e') === 72000.0
                && data_get($payload, 'resultat_carbone.total_tco2e') === 72.0
                && data_get($payload, 'resultat_carbone.categorie_dominante') === 'Production'
                && data_get($payload, 'resultat_carbone.facteur_dominant') === 'Four industriel'
                && data_get($payload, 'facteurs_eleves.0.facteur_nom') === 'Four industriel'
                && count(data_get($payload, 'facteurs_eleves', [])) === 1
                && count(data_get($payload, 'details_resultat', [])) === 2
                && data_get($payload, 'seuils_carbone.seuil_moyen_tco2e') === 10
                && data_get($payload, 'seuils_carbone.seuil_eleve_tco2e') === 50;
            });

        $this->actingAs($user)->post(route('resultats.generate-recommendations', $resultat))
            ->assertRedirect(route('recommandations.show', $resultat));

        $this->assertDatabaseCount('resultat_carbones', 1);
        $this->assertDatabaseCount('suggestion_ias', 1);
        $this->assertDatabaseCount('recommandations', 2);
        $suggestion->refresh();
        $recommendation->refresh();
        $this->assertSame('Nouvelle synthèse prioritaire.', $suggestion->contenu);
        $this->assertSame('20.000', $suggestion->impact_carbone_estime);
        $this->assertSame('Installer un pilotage énergétique plus fin.', $recommendation->action_proposee);
        $this->assertSame('20.000', $recommendation->impact_carbone_estime);
    }

    public function test_it_stays_on_report_when_recommendation_generation_returns_an_empty_n8n_response(): void
    {
        config(['services.n8n.generate_recommendations_url' => 'https://n8n.test/generate-recommendations']);

        Http::fake([
            'https://n8n.test/generate-recommendations' => Http::response('', 200),
        ]);

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
            'total_kg_co2e' => 72000,
            'total_t_co2e_an' => 72,
            'detail_emissions' => [
                [
                    'categorie' => 'production',
                    'label' => 'Production',
                    'facteur_emission_id' => 10,
                    'facteur_nom' => 'Four industriel',
                    'emissions_kgco2e' => 70000,
                    'emissions_tco2e' => 70,
                ],
            ],
            'facteurs_eleves' => [],
            'statut' => 'calcule',
            'calculated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('rapports.show', $resultat))
            ->post(route('resultats.generate-recommendations', $resultat));

        $response->assertRedirect(route('rapports.show', $resultat));
        $response->assertSessionHas('warning', 'Les recommandations n’ont pas pu être générées correctement.');
        $response->assertSessionHas('show_regenerate_recommendations_button', true);
        $response->assertSessionMissing('error');
        $this->assertDatabaseCount('resultat_carbones', 1);
        $this->assertDatabaseCount('suggestion_ias', 0);
        $this->assertDatabaseCount('recommandations', 0);
    }

    public function test_it_shows_acceptable_threshold_messages_when_recommendations_are_not_needed(): void
    {
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
            'postes_emission' => ['transport'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['sections' => [['key' => 'transport', 'title' => 'Transport']]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $resultat = ResultatCarbone::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_genere_id' => $formulaire->id,
            'total_kg_co2e' => 5000,
            'total_t_co2e_an' => 5,
            'detail_emissions' => [
                [
                    'categorie' => 'transport',
                    'label' => 'Transport',
                    'facteur_nom' => 'Transport local',
                    'emissions_kgco2e' => 5000,
                    'emissions_tco2e' => 5,
                ],
            ],
            'facteurs_eleves' => [],
            'statut' => 'calcule',
            'calculated_at' => now(),
        ]);

        Rapport::create([
            'resultat_carbone_id' => $resultat->id,
            'titre' => 'Rapport carbone',
            'resume' => 'Résumé court.',
            'contenu_json' => [
                'resume_executif' => 'Résumé exécutif.',
                'analyse_globale' => 'Analyse globale.',
                'conclusion' => 'Conclusion.',
            ],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        $reportResponse = $this->actingAs($user)->get(route('rapports.show', $resultat));

        $reportResponse->assertOk();
        $reportResponse->assertSeeText('Les seuils d’émission sont acceptables.');
        $reportResponse->assertSeeText('Aucune recommandation prioritaire n’est nécessaire pour ce résultat.');
        $reportResponse->assertDontSeeText('Générer les recommandations');

        $recommendationsResponse = $this->actingAs($user)->get(route('recommandations.show', $resultat));

        $recommendationsResponse->assertOk();
        $recommendationsResponse->assertSeeText('Seuils acceptables');
        $recommendationsResponse->assertSeeText('Aucune recommandation n’est nécessaire pour ce résultat, car les seuils d’émission sont acceptables.');
        $recommendationsResponse->assertDontSeeText('Générer les recommandations');
        $recommendationsResponse->assertDontSeeText('Télécharger PDF');
    }

    public function test_it_keeps_the_result_when_recommendation_generation_fails(): void
    {
        config(['services.n8n.generate_recommendations_url' => 'https://n8n.test/generate-recommendations']);

        Http::fake([
            'https://n8n.test/generate-recommendations' => Http::response([
                'success' => false,
                'message' => 'Facteurs insuffisants',
                'verification' => [
                    'erreurs' => ['Aucun facteur élevé'],
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
            'nombre_employes' => 42,
            'type_production' => 'Production',
            'annee_calcul' => 2026,
        ]);
        $configuration = FormulaireConfiguration::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'postes_emission' => ['transport'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['sections' => [['key' => 'transport', 'title' => 'Transport']]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $resultat = ResultatCarbone::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_genere_id' => $formulaire->id,
            'total_kg_co2e' => 1000,
            'total_t_co2e_an' => 1,
            'detail_emissions' => [],
            'facteurs_eleves' => [],
            'statut' => 'calcule',
            'calculated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('resultats.generate-recommendations', $resultat));

        $response->assertSee("Les recommandations n’ont pas pu être générées correctement.");
        $response->assertSee('Régénérer les recommandations');
        $response->assertDontSee('Facteurs insuffisants');
        $response->assertDontSee('Aucun facteur élevé');
        $this->assertDatabaseCount('resultat_carbones', 1);
        $this->assertDatabaseCount('suggestion_ias', 0);
        $this->assertDatabaseCount('recommandations', 0);
    }

    public function test_it_downloads_report_and_recommendations_as_pdf(): void
    {
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
            'total_kg_co2e' => 72000,
            'total_t_co2e_an' => 72,
            'detail_emissions' => [
                [
                    'categorie' => 'production',
                    'label' => 'Production',
                    'facteur_nom' => 'Four industriel',
                    'emissions_kgco2e' => 70000,
                    'emissions_tco2e' => 70,
                ],
            ],
            'facteurs_eleves' => [],
            'statut' => 'calcule',
            'calculated_at' => now(),
        ]);

        Rapport::create([
            'resultat_carbone_id' => $resultat->id,
            'titre' => 'Rapport carbone',
            'resume' => 'Resume court.',
            'contenu_json' => [
                'resume_executif' => 'Resume executif.',
                'analyse_globale' => 'Analyse globale.',
                'analyse_par_categorie' => [
                    ['categorie' => 'Production', 'analyse' => 'Poste principal.'],
                ],
                'points_critiques' => ['Production elevee.'],
                'recommandations_generales' => ['Optimiser la production.'],
                'conclusion' => 'Conclusion.',
            ],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        SuggestionIa::create([
            'resultat_carbone_id' => $resultat->id,
            'source_emission' => 'Production',
            'contenu' => 'Prioriser la ligne de production.',
            'priorite' => 'elevee',
            'impact_carbone_estime' => 12.5,
        ]);
        Recommandation::create([
            'resultat_carbone_id' => $resultat->id,
            'source_emission' => 'Production',
            'probleme_detecte' => 'Consommation elevee.',
            'action_proposee' => 'Optimiser les cycles de chauffe.',
            'priorite' => 'elevee',
            'impact_carbone_estime' => 12.5,
            'generated_at' => now(),
        ]);

        $reportResponse = $this->actingAs($user)->get(route('rapports.download', $resultat));
        $reportResponse->assertOk();
        $reportResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(
            'attachment; filename="rapport-carbone-'.$resultat->id.'.pdf"',
            $reportResponse->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith('%PDF-', $reportResponse->getContent());

        $recommendationResponse = $this->actingAs($user)->get(route('recommandations.download', $resultat));
        $recommendationResponse->assertOk();
        $recommendationResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(
            'attachment; filename="recommandations-'.$resultat->id.'.pdf"',
            $recommendationResponse->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith('%PDF-', $recommendationResponse->getContent());
    }

    public function test_it_returns_the_expected_message_when_a_factor_is_inactive(): void
    {
        $this->seedFormulas();

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
            'postes_emission' => ['batiment'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['sections' => [['key' => 'batiment', 'title' => 'Bâtiment']]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $facteur = FacteurEmission::create([
            'nom' => 'Électricité bâtiment - réseau Tunisie',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 0.463,
            'source' => 'Source officielle',
            'description' => 'Facteur test',
            'actif' => false,
        ]);

        $response = $this->actingAs($user)->post(route('formulaires.calculate', $formulaire), [
            'lignes' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'champ_calcul' => 'consommation_electricite',
                    'champs' => [
                        'consommation_electricite' => 10000,
                    ],
                    'periode' => 'mois',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['calculation']);
        $this->assertDatabaseCount('resultat_carbones', 0);
    }

    public function test_it_returns_a_clear_error_when_no_calculation_field_is_submitted(): void
    {
        $this->seedFormulas();

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
            'postes_emission' => ['batiment'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => ['sections' => [['key' => 'batiment', 'title' => 'Bâtiment']]],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);
        $facteur = FacteurEmission::create([
            'nom' => 'Électricité bâtiment - réseau Tunisie',
            'categorie' => 'batiment',
            'unite' => 'kWh',
            'coefficient' => 0.463,
            'source' => 'Source officielle',
            'description' => 'Facteur test',
            'actif' => true,
        ]);

        $response = $this->actingAs($user)->post(route('formulaires.calculate', $formulaire), [
            'lignes' => [
                [
                    'categorie' => 'batiment',
                    'facteur_emission_id' => $facteur->id,
                    'champs' => [
                        'consommation_electricite' => 10000,
                    ],
                    'periode' => 'mois',
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'lignes.0.champ_calcul' => 'Aucun champ de calcul n’est défini pour cette section.',
        ]);
        $this->assertDatabaseCount('resultat_carbones', 0);
    }

    private function seedFormulas(): void
    {
        foreach ([
            'annualisation_jour' => 'valeur * jours_declares',
            'annualisation_semaine' => 'valeur * 52',
            'annualisation_mois' => 'valeur * 12',
            'annualisation_trimestre' => 'valeur * 4',
            'annualisation_annee' => 'valeur',
            'calcul_emissions_kgco2e' => 'valeur_annualisee * facteur_emission',
            'conversion_kgco2e_vers_tco2e' => 'emissions_kgco2e / 1000',
            'total_tco2e_an' => 'somme_emissions_tco2e',
        ] as $categorie => $formule) {
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
