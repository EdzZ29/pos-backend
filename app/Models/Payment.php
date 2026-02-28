<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model {
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_method_id', 'processed_by',
        'amount_paid', 'change_amount', 'status', 'reference_number'
    ];

    protected $casts = [
        'amount_paid'   => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function method() {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function processedBy() {
        return $this->belongsTo(User::class, 'processed_by');
    }
}