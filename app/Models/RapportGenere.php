<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RapportGenere extends Model
{
    use HasFactory;

    protected $table = 'rapport_generes';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['downloaded_at' => 'datetime'];
    }

    public function rapport()
    {
        return $this->belongsTo(Rapport::class);
    }

    public function resultatCarbone()
    {
        return $this->belongsTo(ResultatCarbone::class);
    }
}
