<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller {

    public function register(Request $request) {
        $data = $request->validate([
            'role_id'  => 'required|exists:roles,id',
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed', // needs password_confirmation field
        ]);

        $user = User::create([
            'role_id'  => $data['role_id'],
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully',
            'user'    => $user->load('role'),
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request) {
        $data = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $data['login'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Auto clock-in: create a time log entry for this user
        $activeLog = TimeLog::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$activeLog) {
            TimeLog::create([
                'user_id'  => $user->id,
                'clock_in' => Carbon::now(),
                'status'   => 'active',
                'notes'    => 'Auto clock-in on login',
            ]);
        }

        return response()->json([
            'user'  => $user->load('role'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request) {
        $user = $request->user();

        // Auto clock-out: close any active time log for this user
        $activeLog = TimeLog::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($activeLog) {
            $clockOut   = Carbon::now();
            $totalHours = round($activeLog->clock_in->diffInMinutes($clockOut) / 60, 2);

            $activeLog->update([
                'clock_out'   => $clockOut,
                'total_hours' => $totalHours,
                'status'      => 'completed',
                'notes'       => $activeLog->notes
                    ? $activeLog->notes . ' | Auto clock-out on logout'
                    : 'Auto clock-out on logout',
            ]);
        }

        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}