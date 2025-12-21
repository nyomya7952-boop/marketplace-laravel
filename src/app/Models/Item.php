<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path',
        'is_sold',
        'user_id',
        'brand_id',
        'price',
        'description',
        'condition_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'item_category');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function soldItems()
    {
        return $this->hasMany(SoldItem::class);
    }

    /**
     * 商品が購入済みかどうか
     *
     * @return bool
     */
    public function isSold()
    {
        return $this->is_sold === 'sold';
    }

    /**
     * 商品が入金待ちかどうか
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->is_sold === 'pending';
    }

    /**
     * 商品が未購入かどうか
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->is_sold === null || $this->is_sold === false;
    }
}
