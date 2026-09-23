<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'nom_entreprise' => 'Entreprise Test',
            'secteur_activite' => 'Industrie manufacturière',
            'ville' => 'Tunis',
            'pays' => 'Tunisie TN',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('configuration.index', absolute: false));
        $this->assertDatabaseHas('entreprises', [
            'nom' => 'Entreprise Test',
            'secteur_activite' => 'Industrie manufacturière',
            'ville' => 'Tunis',
            'pays' => 'Tunisie TN',
        ]);
    }
}
