<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonneeEmpreinte extends Model
{
    use HasFactory;

    protected $table = 'donnees_empreinte';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'donnees_json' => 'array',
            'emissions_detail_json' => 'array',
        ];
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function formulaireGenere()
    {
        return $this->belongsTo(FormulaireGenere::class);
    }

    public function resultatCarbone()
    {
        return $this->hasOne(ResultatCarbone::class);
    }
}
