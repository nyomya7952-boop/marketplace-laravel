<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterData extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
