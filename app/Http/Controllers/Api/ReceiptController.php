<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show(Order $order)
    {
        $order->load([
            'items.product',
            'items.variant',
            'payment.method',
            'cashier',
        ]);

        $receipt = [
            'store' => [
                'name'    => config('app.store_name', 'My Food Store'),
                'address' => config('app.store_address', '123 Main Street'),
                'phone'   => config('app.store_phone', '09XX-XXX-XXXX'),
            ],
            'receipt' => [
                'order_id'      => $order->id,
                'date'          => $order->created_at->format('M d, Y h:i A'),
                'cashier'       => $order->cashier->name,
                'customer'      => $order->customer_name ?? 'Walk-in',
                'table_number'  => $order->table_number ?? 'N/A',
                'order_type'    => strtoupper($order->order_type),
                'status'        => strtoupper($order->status),
            ],
            'items' => $order->items->map(fn($item) => [
                'name'      => $item->product->name,
                'variant'   => $item->variant?->name ?? null,
                'quantity'  => $item->quantity,
                'unit_price'=> number_format($item->unit_price, 2),
                'subtotal'  => number_format($item->subtotal, 2),
            ]),
            'summary' => [
                'subtotal'       => number_format((float)$order->total_amount + (float)$order->discount_amount, 2),
                'discount_type'  => $order->discount_type ?? 'none',
                'discount_amount'=> number_format((float)$order->discount_amount, 2),
                'total'          => number_format((float)$order->total_amount, 2),
                'amount_paid'    => $order->payment ? number_format((float)$order->payment->amount_paid, 2) : '0.00',
                'change'         => $order->payment ? number_format((float)$order->payment->change_amount, 2) : '0.00',
                'payment_method' => $order->payment?->method->name ?? 'Unpaid',
            ],
        ];

        return response()->json($receipt);
    }

    public function text(Order $order)
    {
        $order->load([
            'items.product',
            'items.variant',
            'payment.method',
            'cashier',
        ]);

        $storeName    = config('app.store_name', 'My Food Store');
        $storeAddress = config('app.store_address', '123 Main Street');
        $storePhone   = config('app.store_phone', '09XX-XXX-XXXX');
        $line         = str_repeat('-', 40);
        $doubleLine   = str_repeat('=', 40);

        $text  = "\n";
        $text .= str_pad($storeName, 40, ' ', STR_PAD_BOTH) . "\n";
        $text .= str_pad($storeAddress, 40, ' ', STR_PAD_BOTH) . "\n";
        $text .= str_pad($storePhone, 40, ' ', STR_PAD_BOTH) . "\n";
        $text .= $doubleLine . "\n";
        $text .= "Order #   : " . $order->id . "\n";
        $text .= "Date      : " . $order->created_at->format('M d, Y h:i A') . "\n";
        $text .= "Cashier   : " . $order->cashier->name . "\n";
        $text .= "Customer  : " . ($order->customer_name ?? 'Walk-in') . "\n";
        $text .= "Table     : " . ($order->table_number ?? 'N/A') . "\n";
        $text .= "Type      : " . strtoupper($order->order_type) . "\n";
        $text .= $line . "\n";
        $text .= str_pad("ITEM", 20) . str_pad("QTY", 5) . str_pad("PRICE", 8) . str_pad("TOTAL", 7) . "\n";
        $text .= $line . "\n";

        foreach ($order->items as $item) {
            $name     = $item->product->name;
            $variant  = $item->variant?->name ? " ({$item->variant->name})" : '';
            $fullName = substr($name . $variant, 0, 19);
            $text .= str_pad($fullName, 20)
                   . str_pad($item->quantity, 5)
                   . str_pad(number_format($item->unit_price, 2), 8)
                   . str_pad(number_format($item->subtotal, 2), 7) . "\n";
        }

        $text .= $line . "\n";
        $subtotal = (float)$order->total_amount + (float)$order->discount_amount;
        $text .= str_pad("SUBTOTAL:", 30)     . str_pad(number_format($subtotal, 2), 10, ' ', STR_PAD_LEFT) . "\n";

        if ($order->discount_type && $order->discount_type !== 'none') {
            $label = strtoupper($order->discount_type) . " DISCOUNT (20%):";
            $text .= str_pad($label, 30) . str_pad('-' . number_format((float)$order->discount_amount, 2), 10, ' ', STR_PAD_LEFT) . "\n";
        }

        $text .= str_pad("TOTAL:", 30)       . str_pad(number_format((float)$order->total_amount, 2), 10, ' ', STR_PAD_LEFT) . "\n";

        if ($order->payment) {
            $text .= str_pad("AMOUNT PAID:", 30)  . str_pad(number_format((float)$order->payment->amount_paid, 2), 10, ' ', STR_PAD_LEFT) . "\n";
            $text .= str_pad("CHANGE:", 30)       . str_pad(number_format((float)$order->payment->change_amount, 2), 10, ' ', STR_PAD_LEFT) . "\n";
            $text .= str_pad("PAYMENT METHOD:", 30) . str_pad($order->payment->method->name, 10, ' ', STR_PAD_LEFT) . "\n";
        }

        $text .= $doubleLine . "\n";
        $text .= str_pad("Thank you! Please come again!", 40, ' ', STR_PAD_BOTH) . "\n\n";

        return response($text)->header('Content-Type', 'text/plain');
    }
}