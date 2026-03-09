<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Addon extends Model {
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'is_available', 'category_id', 'product_id'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function orderItemAddons() {
        return $this->hasMany(OrderItemAddon::class);
    }
}