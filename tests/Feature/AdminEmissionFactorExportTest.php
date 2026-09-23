<?php

namespace Tests\Feature;

use App\Models\FacteurEmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminEmissionFactorExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_filtered_emission_factors_as_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        FacteurEmission::create([
            'nom' => 'Diesel / gasoil',
            'categorie' => 'carburant',
            'unite' => 'litre',
            'coefficient' => 2.68,
            'source' => 'Base CarbonAI',
            'description' => 'Facteur actif',
            'actif' => true,
            'updated_at' => Carbon::parse('2026-07-14 10:30:00'),
        ]);

        FacteurEmission::create([
            'nom' => 'Ancien facteur',
            'categorie' => 'carburant',
            'unite' => 'litre',
            'coefficient' => 1.11,
            'source' => 'Archive',
            'description' => 'Facteur inactif',
            'actif' => false,
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('admin.facteurs.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSeeText('Exporter');
        $indexResponse->assertDontSeeText('Importer');

        $response = $this->actingAs($admin)->get(route('admin.facteurs.export', ['statut' => 'actif']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=facteurs-emission-', (string) $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('ID;Nom;Catégorie;Unité;Coefficient;Source;Description;Statut;"Mise à jour"', $csv);
        $this->assertStringContainsString('Diesel / gasoil', $csv);
        $this->assertStringContainsString('2026-07-14', $csv);
        $this->assertStringNotContainsString('14/07/2026', $csv);
        $this->assertStringContainsString('Actif', $csv);
        $this->assertStringNotContainsString('Ancien facteur', $csv);
        $this->assertStringNotContainsString('Inactif', $csv);
    }
}
