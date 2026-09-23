<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_and_update_a_user_from_the_edit_icon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Ancien Nom',
            'email' => 'ancien@example.test',
            'role' => 'utilisateur',
            'email_verified_at' => now(),
        ]);
        $entreprise = Entreprise::create([
            'user_id' => $user->id,
            'nom' => 'Ancienne Entreprise',
            'secteur_activite' => 'Industrie',
            'pays' => 'Tunisie',
            'ville' => 'Tunis',
            'nombre_employes' => 20,
            'type_production' => 'Production',
            'annee_calcul' => 2026,
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('admin.users.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee(route('admin.users.edit', $user), false);
        $indexResponse->assertDontSeeText('Modification à ajouter');

        $editResponse = $this->actingAs($admin)->get(route('admin.users.edit', $user));

        $editResponse->assertOk();
        $editResponse->assertSeeText('Modifier un utilisateur');
        $editResponse->assertSee('ancien@example.test');

        $updateResponse = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@example.test',
            'entreprise' => 'Nouvelle Entreprise',
            'role' => 'admin',
            'statut' => 'inactif',
        ]);

        $updateResponse->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $entreprise->refresh();

        $this->assertSame('Nouveau Nom', $user->name);
        $this->assertSame('nouveau@example.test', $user->email);
        $this->assertSame('admin', $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('Nouvelle Entreprise', $entreprise->nom);
    }
}
