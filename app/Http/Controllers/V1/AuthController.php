<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('authToken')->plainTextToken;

            return ApiResponseHelper::success(['user' => $user, 'token' => $token], 'User registered successfully', 201);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('An error occured', $e->getMessage(), 400);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                return ApiResponseHelper::error('Invalid credentials', null, 401);
            }

            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            return ApiResponseHelper::success(['user' => $user, 'token' => $token], 'User logged in successfully', 200);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('An error occured', $e->getMessage(), 400);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return ApiResponseHelper::success(null, 'Logged out successfully', 200);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('An error occured', $e->getMessage(), 400);
        }
    }
}
