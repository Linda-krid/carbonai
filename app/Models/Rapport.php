<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'contenu_json' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function resultatCarbone()
    {
        return $this->belongsTo(ResultatCarbone::class);
    }

    public function fichiers()
    {
        return $this->hasMany(RapportGenere::class);
    }
}
