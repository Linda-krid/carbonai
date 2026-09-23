<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resultat_carbone_id')->constrained('resultat_carbones')->cascadeOnDelete();
            $table->string('titre');
            $table->text('resume')->nullable();
            $table->json('contenu_json')->nullable();
            $table->string('statut')->default('brouillon');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
