<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model {
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'description', 'price', 'stock', 'image', 'is_available'];

    protected $casts = [
        'price'        => 'decimal:2',
        'stock'        => 'integer',
        'is_available' => 'boolean',
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function variants() {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems() {
        return $this->hasMany(OrderItem::class);
    }
}