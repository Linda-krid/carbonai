<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacteurDechet extends Model
{
    use HasFactory;

    protected $table = 'facteur_dechets';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['coefficient' => 'decimal:6', 'actif' => 'boolean'];
    }
}
