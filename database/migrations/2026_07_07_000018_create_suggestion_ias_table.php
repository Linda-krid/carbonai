<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suggestion_ias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resultat_carbone_id')->constrained('resultat_carbones')->cascadeOnDelete();
            $table->string('source_emission')->nullable();
            $table->text('contenu');
            $table->string('priorite')->default('moyenne');
            $table->decimal('impact_carbone_estime', 15, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestion_ias');
    }
};
