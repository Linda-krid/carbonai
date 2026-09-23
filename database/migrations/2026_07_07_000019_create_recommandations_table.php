<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommandations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resultat_carbone_id')->constrained('resultat_carbones')->cascadeOnDelete();
            $table->string('source_emission');
            $table->text('probleme_detecte');
            $table->text('action_proposee');
            $table->enum('priorite', ['faible', 'moyenne', 'elevee'])->default('moyenne');
            $table->decimal('impact_carbone_estime', 15, 3)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommandations');
    }
};
