<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(['success' => true, 'data' => [
            'token' => $token,
            'user' => ['id' => $user->id, 'name' => $user->name, 'user_type' => $user->user_type],
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج']);
    }
}
