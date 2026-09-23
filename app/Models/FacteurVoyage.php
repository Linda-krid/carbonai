<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacteurVoyage extends Model
{
    use HasFactory;

    protected $table = 'facteur_voyages';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['coefficient' => 'decimal:6', 'actif' => 'boolean'];
    }
}
