<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller {
    public function index() {
        return response()->json(Order::with(['cashier', 'items.product', 'payment.method'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'customer_name'  => 'nullable|string',
            'table_number'   => 'nullable|string',
            'order_type'     => 'required|in:dine-in,takeout,delivery',
            'discount_type'  => 'nullable|string|in:none,senior,pwd',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes'      => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $data) {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);

            $discountType   = $data['discount_type'] ?? 'none';
            $discountAmount = 0;
            if (in_array($discountType, ['senior', 'pwd'])) {
                $discountAmount = round($subtotal * 0.20, 2);
            }
            $total = $subtotal - $discountAmount;

            $order = Order::create([
                'user_id'         => $request->user()->id,
                'customer_name'   => $data['customer_name'] ?? null,
                'table_number'    => $data['table_number'] ?? null,
                'order_type'      => $data['order_type'],
                'discount_type'   => $discountType,
                'discount_amount' => $discountAmount,
                'notes'           => $data['notes'] ?? null,
                'total_amount'    => $total,
            ]);

            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $item['unit_price'] * $item['quantity'],
                    'notes'      => $item['notes'] ?? null,
                ]);
            }

            return response()->json($order->load('items'), 201);
        });
    }

    public function show(Order $order) {
        return response()->json($order->load(['cashier', 'items.product', 'items.variant', 'payment.method']));
    }

    public function update(Request $request, Order $order) {
        $data = $request->validate([
            'status' => 'required|in:pending,preparing,completed,cancelled',
        ]);
        $order->update($data);
        return response()->json($order);
    }

    public function destroy(Order $order) {
        $order->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}