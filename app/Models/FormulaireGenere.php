<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormulaireGenere extends Model
{
    use HasFactory;

    protected $table = 'formulaire_generes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function configuration()
    {
        return $this->belongsTo(FormulaireConfiguration::class, 'formulaire_configuration_id');
    }

    public function donneesEmpreinte()
    {
        return $this->hasMany(DonneeEmpreinte::class);
    }

    public function resultatsCarbone()
    {
        return $this->hasMany(ResultatCarbone::class);
    }
}
