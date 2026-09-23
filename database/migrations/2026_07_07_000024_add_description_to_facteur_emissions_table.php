<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facteur_emissions', function (Blueprint $table) {
            $table->text('description')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('facteur_emissions', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
