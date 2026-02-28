<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller {
    public function index() {
        return response()->json(Role::all());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:roles',
        ]);
        return response()->json(Role::create($data), 201);
    }

    public function show(Role $role) {
        return response()->json($role);
    }

    public function update(Request $request, Role $role) {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:roles,slug,' . $role->id,
        ]);
        $role->update($data);
        return response()->json($role);
    }

    public function destroy(Role $role) {
        $role->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}