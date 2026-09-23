<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['details_json' => 'array'];
    }

    public function resultatCarbone()
    {
        return $this->belongsTo(ResultatCarbone::class);
    }
}
