<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'user_id',
        'payment_method_id',
        'shipping_postal_code',
        'shipping_address',
        'shipping_building_name',
        'stripe_session_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(MasterData::class, 'payment_method_id');
    }
}
