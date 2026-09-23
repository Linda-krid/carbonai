<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resultat_carbones')) {
            return;
        }

        $columns = collect([
            'base_carbone_taxable',
            'tva_carbone_estimee',
            'tva_carbone_tnd',
            'prix_carbone',
            'taux_tva',
            'devise_tva',
            'seuil_taxable',
            'reduction',
            'credit_carbone',
        ])->filter(fn (string $column) => Schema::hasColumn('resultat_carbones', $column))->values()->all();

        if ($columns === []) {
            return;
        }

        Schema::table('resultat_carbones', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('resultat_carbones')) {
            return;
        }

        Schema::table('resultat_carbones', function (Blueprint $table) {
            if (! Schema::hasColumn('resultat_carbones', 'base_carbone_taxable')) {
                $table->decimal('base_carbone_taxable', 15, 3)->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'tva_carbone_estimee')) {
                $table->decimal('tva_carbone_estimee', 15, 3)->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'tva_carbone_tnd')) {
                $table->decimal('tva_carbone_tnd', 15, 3)->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'prix_carbone')) {
                $table->decimal('prix_carbone', 15, 3)->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'taux_tva')) {
                $table->decimal('taux_tva', 8, 3)->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'devise_tva')) {
                $table->string('devise_tva')->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'seuil_taxable')) {
                $table->decimal('seuil_taxable', 15, 3)->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'reduction')) {
                $table->decimal('reduction', 15, 3)->nullable();
            }

            if (! Schema::hasColumn('resultat_carbones', 'credit_carbone')) {
                $table->decimal('credit_carbone', 15, 3)->nullable();
            }
        });
    }
};
