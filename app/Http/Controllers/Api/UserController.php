<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller {
    public function index() {
        return response()->json(User::with('role')->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'role_id'  => 'required|exists:roles,id',
            'name'     => 'required|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);
        $data['password'] = Hash::make($data['password']);
        return response()->json(User::create($data)->load('role'), 201);
    }

    public function show(User $user) {
        return response()->json($user->load('role'));
    }

    public function update(Request $request, User $user) {
        $data = $request->validate([
            'role_id'   => 'sometimes|exists:roles,id',
            'name'      => 'sometimes|string|max:255',
            'username'  => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            'password'  => 'sometimes|string|min:6',
            'is_active' => 'sometimes|boolean',
        ]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json($user);
    }

    public function destroy(User $user) {
        $user->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}