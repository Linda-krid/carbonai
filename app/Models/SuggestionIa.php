<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestionIa extends Model
{
    use HasFactory;

    protected $table = 'suggestion_ias';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['impact_carbone_estime' => 'decimal:3'];
    }

    public function resultatCarbone()
    {
        return $this->belongsTo(ResultatCarbone::class);
    }
}
