<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NcSemiFini extends Model
{
    protected $table = 'nc_semi_fini';

    protected $fillable = [
        'numero_article', 'description_article', 'quantite',
        'nbr_palettes', 'poids_total_kg', 'motif', 'decision_sq',
    ];

    protected $casts = [
        'quantite'       => 'integer',
        'nbr_palettes'   => 'integer',
        'poids_total_kg' => 'decimal:2',
    ];

    /** Poids total en tonnes (kg / 1000). */
    public function getPoidsTotalTAttribute(): float
    {
        return (float) $this->poids_total_kg / 1000;
    }
}
