<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'resident')->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

    public function show(User $user): JsonResponse
    {
        if ($user->role !== 'resident') {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json([
            'user' => $user,
            'activity' => [
                'reports_count' => $user->reports()->count(),
                'service_requests_count' => $user->serviceRequests()->count(),
                'recent_reports' => $user->reports()->latest()->take(5)->get(),
                'recent_requests' => $user->serviceRequests()->latest()->take(5)->get(),
            ],
        ]);
    }

    public function suspend(User $user): JsonResponse
    {
        if ($user->role !== 'resident') {
            return response()->json(['message' => 'Cannot suspend this user.'], 422);
        }

        $user->update(['is_suspended' => true]);

        return response()->json(['message' => 'Account suspended.', 'user' => $user]);
    }

    public function unsuspend(User $user): JsonResponse
    {
        $user->update(['is_suspended' => false]);

        return response()->json(['message' => 'Account reactivated.', 'user' => $user]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
