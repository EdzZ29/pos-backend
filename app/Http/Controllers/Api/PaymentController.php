<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller {
    public function index() {
        return response()->json(Payment::with(['order', 'method', 'processedBy'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'order_id'          => 'required|exists:orders,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount_paid'       => 'required|numeric|min:0',
            'reference_number'  => 'nullable|string',
        ]);

        $order = \App\Models\Order::findOrFail($data['order_id']);

        $payment = Payment::create([
            'order_id'          => $data['order_id'],
            'payment_method_id' => $data['payment_method_id'],
            'processed_by'      => $request->user()->id,
            'amount_paid'       => $data['amount_paid'],
            'change_amount'     => max(0, $data['amount_paid'] - $order->total_amount),
            'reference_number'  => $data['reference_number'] ?? null,
            'status'            => 'paid',
        ]);

        $order->update(['status' => 'completed']);

        return response()->json($payment->load(['order', 'method']), 201);
    }

    public function show(Payment $payment) {
        return response()->json($payment->load(['order', 'method', 'processedBy']));
    }

    public function update(Request $request, Payment $payment) {
        $data = $request->validate([
            'status' => 'required|in:paid,pending,refunded',
        ]);
        $payment->update($data);
        return response()->json($payment);
    }

    public function destroy(Payment $payment) {
        $payment->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}