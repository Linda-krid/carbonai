<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\FacteurEmission;
use App\Models\FormulaireConfiguration;
use App\Models\FormulaireGenere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratedFormViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_generated_form_with_n8n_section_format(): void
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
            'postes_emission' => ['batiment'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => [
                'success' => true,
                'statut' => 'formulaire_valide',
                'message' => 'Formulaire généré avec succès',
                'formulaire' => [
                    'statut' => 'genere',
                    'nom_formulaire' => 'Formulaire carbone',
                    'sections' => [
                        [
                            'categorie' => 'batiment',
                            'titre' => 'Consommation d\'eau',
                            'description' => 'Test de section n8n',
                            'champs' => [
                                [
                                    'nom' => 'quantite_consommee',
                                    'label' => 'Quantité consommée',
                                    'type' => 'number',
                                    'unite' => 'tonne',
                                    'obligatoire' => true,
                                    'periodes' => ['annee', 'mois'],
                                    'utilise_pour_calcul' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('formulaires.show', $formulaire));

        $response->assertOk();
        $response->assertDontSeeText('Formulaire généré avec succès');
        $response->assertSeeText('Consommation d\'eau');
        $response->assertSee(route('formulaires.calculate', $formulaire), false);
        $response->assertSeeHtml('enctype="multipart/form-data"');
        $response->assertSeeHtml('name="lignes[0][categorie]"');
        $response->assertSeeHtml('name="lignes[0][facteur_emission_id]"');
        $response->assertSeeHtml('name="lignes[0][champ_calcul]" value="quantite_consommee"');
        $response->assertSeeHtml('name="lignes[0][champs][quantite_consommee]"');
        $response->assertSeeHtml('data-calculation-field="true"');
        $response->assertSeeHtml('value="annee"');
        $response->assertSeeHtml('value="mois"');
        $response->assertDontSeeHtml('value="semaine"');
        $response->assertSeeHtml('name="lignes[0][date_debut]"');
        $response->assertSeeHtml('name="lignes[0][date_fin]"');
        $response->assertSeeHtml('name="lignes[0][justificatif]"');
        $response->assertSeeHtml('type="file"');
        $response->assertSeeHtml('accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"');
        $response->assertSeeText('Ajouter un justificatif');
        $response->assertSeeText('PDF, image ou document');
        $response->assertDontSeeText('Valeur saisie');
        $response->assertDontSeeText('Choisissez');
        $response->assertDontSeeText('Aucun fichier choisi');
        $response->assertDontSeeText('Choose file');
        $response->assertDontSeeHtml('<option value="">Sélectionner</option>');
    }

    public function test_it_renders_normalized_dynamic_fields_from_formulaire_sections(): void
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
        FacteurEmission::create([
            'nom' => 'Diesel test',
            'categorie' => 'carburant',
            'unite' => 'litre',
            'coefficient' => 2.68,
            'source' => 'Source test',
            'description' => 'Facteur test',
            'actif' => true,
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => [
                'success' => true,
                'statut' => 'formulaire_valide',
                'message' => 'Formulaire généré avec succès',
                'formulaire' => [
                    'statut' => 'genere',
                    'sections' => [
                        [
                            'categorie' => 'carburant',
                            'titre' => 'Carburant',
                            'description' => 'Données de carburant',
                            'champs' => [
                                ['nom' => 'reference', 'label' => 'Référence', 'type' => 'text', 'obligatoire' => true],
                                ['nom' => 'quantite', 'label' => 'Quantité', 'type' => 'number', 'unite' => 'L', 'utilise_pour_calcul' => true],
                                ['nom' => 'energie', 'label' => 'Énergie', 'type' => 'select', 'options' => ['diesel' => 'Diesel', 'essence' => 'Essence']],
                                ['nom' => 'type_carburant', 'label' => 'Type de carburant', 'type' => 'select', 'options' => []],
                                ['nom' => 'commentaire', 'label' => 'Commentaire', 'type' => 'textarea'],
                                ['nom' => 'date_releve', 'label' => 'Date du relevé', 'type' => 'date'],
                            ],
                        ],
                    ],
                ],
            ],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('formulaires.show', $formulaire));

        $response->assertOk();
        $response->assertDontSeeText('Aucune section disponible');
        $response->assertSeeText('Carburant');
        $response->assertSeeHtml('name="lignes[0][champ_calcul]" value="quantite"');
        $response->assertSeeHtml('name="lignes[0][champs][reference]"');
        $response->assertSeeHtml('name="lignes[0][champs][quantite]"');
        $response->assertSeeHtml('name="lignes[0][champs][energie]"');
        $response->assertSeeHtml('value="diesel"');
        $response->assertSeeHtml('name="lignes[0][champs][type_carburant]"');
        $response->assertSeeHtml('value="Diesel test"');
        $response->assertSeeHtml('name="lignes[0][champs][commentaire]"');
        $response->assertSeeHtml('name="lignes[0][champs][date_releve]"');
        $response->assertSeeHtml('data-calculation-field="true"');
        $response->assertSeeHtml('type="date"');
        $response->assertSeeText('L');
        $response->assertDontSeeText('Valeur saisie');
    }

    public function test_it_uses_normalized_section_keys_to_load_factor_options(): void
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
            'postes_emission' => ['machines', 'batiment', 'materiaux', 'dechets', 'employes', 'transport_personnel'],
            'statut' => 'configuration',
        ]);

        foreach ([
            'machines' => 'Facteur machines',
            'batiment' => 'Facteur bâtiment',
            'materiaux' => 'Facteur matériaux',
            'dechets' => 'Facteur déchets',
            'employes' => 'Facteur employés',
            'transport_personnel' => 'Facteur transport personnel',
        ] as $categorie => $nom) {
            FacteurEmission::create([
                'nom' => $nom,
                'categorie' => $categorie,
                'unite' => 'kWh',
                'coefficient' => 0.463,
                'source' => 'Source test',
                'description' => 'Facteur test',
                'actif' => true,
            ]);
        }

        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => [
                'success' => true,
                'formulaire' => [
                    'statut' => 'genere',
                    'sections' => [
                        ['categorie' => 'Machines et équipements', 'titre' => 'Machines et équipements', 'champs' => []],
                        ['categorie' => 'Énergie', 'titre' => 'Énergie', 'champs' => []],
                        ['categorie' => 'Matériel', 'titre' => 'Matériel', 'champs' => []],
                        ['categorie' => 'Déchets solides', 'titre' => 'Déchets solides', 'champs' => []],
                        ['categorie' => 'Employés', 'titre' => 'Employés', 'champs' => []],
                        ['categorie' => 'Transport personnel', 'titre' => 'Transport personnel', 'champs' => []],
                    ],
                ],
            ],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('formulaires.show', $formulaire));

        $response->assertOk();
        $response->assertSeeText('Facteur machines');
        $response->assertSeeText('Facteur bâtiment');
        $response->assertSeeText('Facteur matériaux');
        $response->assertSeeText('Facteur déchets');
        $response->assertSeeText('Facteur employés');
        $response->assertSeeText('Facteur transport personnel');
        $response->assertDontSeeText("Aucun facteur actif n'est configuré pour cette catégorie.");
    }

    public function test_it_adds_fallback_sections_for_selected_categories_missing_from_n8n_response(): void
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
            'postes_emission' => ['carburant', 'transport', 'production'],
            'statut' => 'configuration',
        ]);
        $formulaire = FormulaireGenere::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => [
                'success' => true,
                'formulaire' => [
                    'statut' => 'genere',
                    'sections' => [
                        ['categorie' => 'carburant', 'titre' => 'Carburant', 'champs' => []],
                    ],
                ],
            ],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('formulaires.show', $formulaire));

        $response->assertOk();
        $response->assertDontSeeText('Aucune section disponible');
        $response->assertSeeText('Carburant');
        $response->assertSeeText('Transport');
        $response->assertSeeText('Production');
        $response->assertSeeHtml('name="lignes[1][categorie]"');
        $response->assertSeeHtml('name="lignes[2][categorie]"');
    }

    public function test_it_renders_the_exact_formulaire_11_route(): void
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
            'postes_emission' => ['batiment'],
            'statut' => 'configuration',
        ]);

        $formulaire = FormulaireGenere::create([
            'id' => 11,
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'formulaire_configuration_id' => $configuration->id,
            'schema_json' => [
                'success' => true,
                'statut' => 'formulaire_valide',
                'message' => 'Formulaire généré avec succès',
                'formulaire' => [
                    'statut' => 'genere',
                    'nom_formulaire' => 'Formulaire carbone',
                    'sections' => [
                        [
                            'categorie' => 'batiment',
                            'titre' => 'Consommation d\'eau',
                            'description' => 'Test de section n8n',
                            'champs' => [],
                        ],
                    ],
                ],
            ],
            'statut' => 'genere',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/formulaire-genere/11');

        $response->assertOk();
        $response->assertSeeText('Consommation d\'eau');
        $response->assertDontSeeText('Formulaire généré avec succès');
    }
}
