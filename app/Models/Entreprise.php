<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entreprise extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function formulaireConfigurations()
    {
        return $this->hasMany(FormulaireConfiguration::class);
    }

    public function formulaireGeneres()
    {
        return $this->hasMany(FormulaireGenere::class);
    }

    public function resultatsCarbone()
    {
        return $this->hasMany(ResultatCarbone::class);
    }
}
