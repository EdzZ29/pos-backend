<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use Illuminate\Http\Request;

class AddonController extends Controller {
    public function index() {
        return response()->json(Addon::with(['category', 'product'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'is_available' => 'sometimes|boolean',
            'category_id'  => 'nullable|exists:categories,id',
            'product_id'   => 'nullable|exists:products,id',
        ]);
        $addon = Addon::create($data);
        return response()->json($addon->load(['category', 'product']), 201);
    }

    public function show(Addon $addon) {
        return response()->json($addon->load(['category', 'product']));
    }

    public function update(Request $request, Addon $addon) {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'sometimes|numeric|min:0',
            'is_available' => 'sometimes|boolean',
            'category_id'  => 'nullable|exists:categories,id',
            'product_id'   => 'nullable|exists:products,id',
        ]);
        $addon->update($data);
        return response()->json($addon->load(['category', 'product']));
    }

    public function destroy(Addon $addon) {
        $addon->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}