<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeLogController extends Controller
{
    /**
     * List all time logs (with user relation), newest first.
     */
    public function index(Request $request)
    {
        $query = TimeLog::with('user.role')->latest('clock_in');

        // Optional filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date')) {
            $query->whereDate('clock_in', $request->date);
        }

        return response()->json($query->get());
    }

    /**
     * Show a single time log.
     */
    public function show(TimeLog $timeLog)
    {
        return response()->json($timeLog->load('user.role'));
    }

    /**
     * Clock in a user (create a new time log entry).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes'   => 'nullable|string',
        ]);

        // Check if user already has an active clock-in
        $active = TimeLog::where('user_id', $data['user_id'])
            ->where('status', 'active')
            ->first();

        if ($active) {
            return response()->json([
                'message' => 'User already has an active shift. Please clock out first.',
                'active_log' => $active->load('user.role'),
            ], 422);
        }

        $log = TimeLog::create([
            'user_id'  => $data['user_id'],
            'clock_in' => Carbon::now(),
            'status'   => 'active',
            'notes'    => $data['notes'] ?? null,
        ]);

        return response()->json($log->load('user.role'), 201);
    }

    /**
     * Clock out a user (update an existing time log).
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if ($timeLog->status === 'completed') {
            return response()->json(['message' => 'This shift has already been completed.'], 422);
        }

        $clockOut   = Carbon::now();
        $totalHours = round($timeLog->clock_in->diffInMinutes($clockOut) / 60, 2);

        $timeLog->update([
            'clock_out'   => $clockOut,
            'total_hours' => $totalHours,
            'status'      => 'completed',
            'notes'       => $data['notes'] ?? $timeLog->notes,
        ]);

        return response()->json($timeLog->load('user.role'));
    }

    /**
     * Mark a user as absent / no duty for today.
     */
    public function markAbsent(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes'   => 'nullable|string',
        ]);

        // Check if user already has a log today
        $existing = TimeLog::where('user_id', $data['user_id'])
            ->whereDate('clock_in', Carbon::today())
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'User already has a time log for today.',
            ], 422);
        }

        $log = TimeLog::create([
            'user_id'     => $data['user_id'],
            'clock_in'    => Carbon::now(),
            'clock_out'   => Carbon::now(),
            'total_hours' => 0,
            'status'      => 'absent',
            'notes'       => $data['notes'] ?? 'No duty / Absent',
        ]);

        return response()->json($log->load('user.role'), 201);
    }

    /**
     * QR-based attendance: toggle clock-in / clock-out.
     * If user has an active shift → clock out.
     * If no active shift → clock in.
     */
    public function qrScan(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::with('role')->findOrFail($data['user_id']);

        // Check for active shift
        $active = TimeLog::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($active) {
            // Clock out
            $clockOut   = Carbon::now();
            $totalHours = round($active->clock_in->diffInMinutes($clockOut) / 60, 2);

            $active->update([
                'clock_out'   => $clockOut,
                'total_hours' => $totalHours,
                'status'      => 'completed',
                'notes'       => $active->notes
                    ? $active->notes . ' | QR clock-out'
                    : 'QR clock-out',
            ]);

            return response()->json([
                'action'   => 'clock_out',
                'message'  => "{$user->name} clocked out successfully.",
                'user'     => $user,
                'time_log' => $active->load('user.role'),
            ]);
        } else {
            // Clock in
            $log = TimeLog::create([
                'user_id'  => $user->id,
                'clock_in' => Carbon::now(),
                'status'   => 'active',
                'notes'    => 'QR clock-in',
            ]);

            return response()->json([
                'action'   => 'clock_in',
                'message'  => "{$user->name} clocked in successfully.",
                'user'     => $user,
                'time_log' => $log->load('user.role'),
            ], 201);
        }
    }
}
