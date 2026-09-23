<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultatCarbone extends Model
{
    use HasFactory;

    protected $table = 'resultat_carbones';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_kg_co2e' => 'decimal:3',
            'total_t_co2e_an' => 'decimal:3',
            'detail_emissions' => 'array',
            'facteurs_eleves' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function getStatutCalculAttribute(): ?string
    {
        return $this->statut;
    }

    public function getDetailsCalculAttribute(): array
    {
        return $this->detail_emissions ?? [];
    }

    public function getTotalTco2eAttribute(): ?string
    {
        return $this->total_t_co2e_an;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function formulaireGenere()
    {
        return $this->belongsTo(FormulaireGenere::class);
    }

    public function donneesEmpreinte()
    {
        return $this->belongsTo(DonneeEmpreinte::class);
    }

    public function recommandations()
    {
        return $this->hasMany(Recommandation::class);
    }

    public function rapports()
    {
        return $this->hasMany(Rapport::class);
    }

    public function suggestionIas()
    {
        return $this->hasMany(SuggestionIa::class);
    }
}
