<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller {
    public function index() {
        return response()->json(Product::with(['category', 'variants'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'size'        => 'nullable|string|in:R,S,M,L,XL',
            'image'       => 'nullable|string',
        ]);
        return response()->json(Product::create($data), 201);
    }

    public function show(Product $product) {
        return response()->json($product->load(['category', 'variants']));
    }

    public function update(Request $request, Product $product) {
        $data = $request->validate([
            'category_id'  => 'sometimes|exists:categories,id',
            'name'         => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'sometimes|numeric|min:0',
            'size'         => 'nullable|string|in:R,S,M,L,XL',
            'is_available' => 'sometimes|boolean',
        ]);
        $product->update($data);
        return response()->json($product);
    }

    public function destroy(Product $product) {
        $product->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}