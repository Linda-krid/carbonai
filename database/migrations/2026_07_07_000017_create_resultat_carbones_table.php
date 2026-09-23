<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultat_carbones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->foreignId('formulaire_genere_id')->constrained('formulaire_generes')->cascadeOnDelete();
            $table->foreignId('donnees_empreinte_id')->nullable()->constrained('donnees_empreinte')->nullOnDelete();
            $table->decimal('total_kg_co2e', 15, 3)->default(0);
            $table->decimal('total_t_co2e_an', 15, 3)->default(0);
            $table->json('detail_emissions')->nullable();
            $table->json('facteurs_eleves')->nullable();
            $table->string('statut')->default('calcule');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultat_carbones');
    }
};
