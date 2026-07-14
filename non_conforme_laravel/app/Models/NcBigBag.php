<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NcBigBag extends Model
{
    protected $table = 'nc_big_bag';

    protected $fillable = [
        'date_nc', 'total_big_bag', 'tonnage',
    ];

    protected $casts = [
        'date_nc'       => 'date',
        'total_big_bag' => 'integer',
        'tonnage'       => 'decimal:2',
    ];

    /** Tonnage en tonnes (kg / 1000). */
    public function getTonnageTAttribute(): float
    {
        return (float) $this->tonnage / 1000;
    }
}
