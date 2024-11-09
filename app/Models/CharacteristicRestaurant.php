<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CharacteristicRestaurant extends Pivot
{
    use HasFactory;
    protected $casts = [
        'id'=>'integer',
        'restaurant_id'=>'integer',
        'characteristic_id'=>'integer'
    ];

}
