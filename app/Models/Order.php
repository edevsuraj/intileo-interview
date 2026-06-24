<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //

    public $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'status',
        'total_amount',
        'discount_type',
        'disount_condition',
        'discount_amount',
        'discount_percentage',
        'final_amount'
    ];
}
