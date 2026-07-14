<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NcDesinfectant extends Model
{
    protected $table = 'nc_desinfectant';

    protected $fillable = [
        'type_article', 'numero_article', 'description_article', 'stock_mag',
        'date_expiration', 'code_um', 'nbr_palettes', 'prix_unitaire',
        'poids_par_carton_kg', 'date_blocage', 'motif', 'responsable', 'decision_cq',
    ];

    protected $casts = [
        'stock_mag'           => 'integer',
        'nbr_palettes'        => 'integer',
        'prix_unitaire'       => 'decimal:4',
        'poids_par_carton_kg' => 'decimal:4',
        'date_expiration'     => 'date',
        'date_blocage'        => 'date',
    ];

    /** Valeur totale = stock × prix unitaire. */
    public function getTotalAttribute(): float
    {
        return (float) $this->stock_mag * (float) $this->prix_unitaire;
    }

    /** Poids total (kg) = stock × poids par carton. */
    public function getPoidsTotalKgAttribute(): float
    {
        return (float) $this->stock_mag * (float) $this->poids_par_carton_kg;
    }

    /** Article expiré (date d'expiration dépassée). */
    public function getIsExpiredAttribute(): bool
    {
        return $this->date_expiration !== null && $this->date_expiration->isPast();
    }
}
