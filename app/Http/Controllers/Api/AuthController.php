<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'national_id' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:customer,driver',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'national_id' => $request->national_id,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'customer',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login user with Email, Phone, or National ID
     */
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', // Can be email, phone, or national_id
            'password' => 'required',
        ]);

        // Try to find user by email, phone, or national_id
        $user = User::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->orWhere('national_id', $request->identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete old tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('driver');

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Get all users (admin/debug endpoint)
     */
    public function allUsers(Request $request)
    {
        // TODO: Add admin authentication check
        $users = User::select('id', 'name', 'email', 'phone', 'national_id', 'role', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'data' => $users,
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
            'profile_photo' => 'sometimes|image|max:2048',
        ]);

        $user->update($request->only(['name', 'email', 'phone']));

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $user->update(['profile_photo' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Verify phone number (OTP)
     */
    public function verifyPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        // TODO: Implement OTP verification logic with SMS gateway
        // For now, accept any 6-digit code

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found',
            ], 404);
        }

        $user->update(['phone_verified_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully',
        ]);
    }

    /**
     * Forgot password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // TODO: Send password reset email
        // For now, just return success

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email',
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // TODO: Validate reset token
        // For now, just update password

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }
}
