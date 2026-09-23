<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(FormulaTemplateSeeder::class);

        User::updateOrCreate(
            ['email' => 'admin@carbon.test'],
            [
                'name' => 'Admin Carbon',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'entreprise@carbon.test'],
            [
                'name' => 'Entreprise Demo',
                'password' => Hash::make('password'),
                'role' => 'utilisateur',
                'email_verified_at' => now(),
            ]
        );
    }
}
