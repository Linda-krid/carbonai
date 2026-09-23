<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recommandation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'impact_carbone_estime' => 'decimal:3',
            'generated_at' => 'datetime',
        ];
    }

    public function resultatCarbone()
    {
        return $this->belongsTo(ResultatCarbone::class);
    }
}
