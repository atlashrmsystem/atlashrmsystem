<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $maxAttempts = max(1, (int) env('LOGIN_RATE_LIMIT_ATTEMPTS', 8));
        $decaySeconds = max(1, (int) env('LOGIN_RATE_LIMIT_DECAY_SECONDS', 60));
        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        $rateLimiterAvailable = true;
        try {
            if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($throttleKey);

                return response()->json([
                    'message' => "Too many login attempts. Try again in {$seconds} seconds.",
                ], 429, ['Retry-After' => $seconds]);
            }
        } catch (Throwable $e) {
            $rateLimiterAvailable = false;
            report($e);
        }

        $user = User::where('email', $request->input('email'))->first();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            if ($rateLimiterAvailable) {
                try {
                    RateLimiter::hit($throttleKey, $decaySeconds);
                } catch (Throwable $e) {
                    report($e);
                }
            }

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($rateLimiterAvailable) {
            try {
                RateLimiter::clear($throttleKey);
            } catch (Throwable $e) {
                report($e);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json($this->buildAuthPayload($user, $token));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json($this->buildAuthPayload($user, $token), 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        $user = $request->user();
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    private function buildAuthPayload(User $user, string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'role_names' => $user->getRoleNames()->values(),
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
