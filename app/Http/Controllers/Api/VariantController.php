<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    public function index(Product $product)
    {
        return response()->json($product->variants);
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'additional_price' => 'required|numeric|min:0',
            'is_available' => 'boolean',
        ]);

        $variant = $product->variants()->create($data);
        return response()->json($variant, 201);
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:50',
            'additional_price' => 'sometimes|numeric|min:0',
            'is_available' => 'sometimes|boolean',
        ]);

        $variant->update($data);
        return response()->json($variant);
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        $variant->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    // Bulk sync variants for a product
    public function sync(Request $request, Product $product)
    {
        $data = $request->validate([
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable',
            'variants.*.name' => 'required|string|max:50',
            'variants.*.additional_price' => 'required|numeric|min:0',
        ]);

        $variants = $data['variants'] ?? [];
        $existingIds = [];
        
        foreach ($variants as $variantData) {
            $variantId = !empty($variantData['id']) ? intval($variantData['id']) : null;
            
            if ($variantId) {
                // Update existing
                $variant = $product->variants()->find($variantId);
                if ($variant) {
                    $variant->update([
                        'name' => $variantData['name'],
                        'additional_price' => $variantData['additional_price'],
                    ]);
                    $existingIds[] = $variant->id;
                }
            } else {
                // Create new
                $variant = $product->variants()->create([
                    'name' => $variantData['name'],
                    'additional_price' => $variantData['additional_price'],
                ]);
                $existingIds[] = $variant->id;
            }
        }

        // Delete variants that were removed (or all if empty array sent)
        if (count($existingIds) > 0) {
            $product->variants()->whereNotIn('id', $existingIds)->delete();
        } else {
            $product->variants()->delete();
        }

        return response()->json($product->load('variants'));
    }
}
