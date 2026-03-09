<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItemAddon extends Model {
    use HasFactory;

    protected $fillable = [
        'order_item_id', 'addon_id',
        'quantity', 'unit_price', 'subtotal'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function orderItem() {
        return $this->belongsTo(OrderItem::class);
    }

    public function addon() {
        return $this->belongsTo(Addon::class);
    }
}