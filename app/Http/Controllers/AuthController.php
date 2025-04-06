<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // Validate incoming request
            $validated = $request->validate([
                'user_id'    => 'nullable|integer|exists:users,id',
                'name'       => 'required|string|max:255',
                'nickName'   => 'nullable|string|max:255',
                'email'      => 'required|email|unique:users,email',
                'password'   => 'required|string|min:6',
                'phone'      => 'nullable|string|max:255',
                'profileUrl' => 'nullable|url|max:2048',
                'bgUrl'      => 'nullable|url|max:2048',
                'bio'        => 'nullable|string|max:1000',
                'instagram'  => 'nullable|url|max:255',
                'facebook'   => 'nullable|url|max:255',
                'userType'   => 'required|string|max:50',
                'isOnline'   => 'sometimes|boolean',
                'userStatus' => 'sometimes|string|max:50'
            ]);

            Log::info('User registration data received', ['data' => $request->all()]);

            // Create user record
            $user = User::create(array_merge($validated, [
                'user_id'    => isset($validated['user_id']) && is_numeric($validated['user_id'])
                    ? (int) $validated['user_id'] : null,
                'password'   => bcrypt($validated['password']),
                'isOnline'   => $validated['isOnline'] ?? false,
                'userStatus' => $validated['userStatus'] ?? 'active',
            ]));

            // Generate secure token
            $token = $user->createToken('crispy_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully.',
                'token'   => $token,
                'user'    => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred during registration.',
            ], 500);
        }
    }

    /**
     * Authenticate the user and issue a secure token.
     *
     * Implements rate limiting to prevent brute force attacks.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $email = Str::lower($request->input('email'));
        $throttleKey = $email . '|' . $request->ip();

        // Rate limit: max 5 attempts per minute
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            Log::warning('Rate limit exceeded for login', ['email' => $email, 'ip' => $request->ip()]);
            return response()->json([
                'message' => 'Too many login attempts. Try again in ' . RateLimiter::availableIn($throttleKey) . ' seconds.',
            ], 429);
        }

        // Validate credentials
        $request->validate([
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            RateLimiter::hit($throttleKey, 60);
            Log::error('Invalid login attempt', ['email' => $email]);
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        $user = Auth::guard('web')->user();
        RateLimiter::clear($throttleKey);
        Log::info('User authenticated', ['user_id' => $user->id]);

        // Issue token using Sanctum
        $token = $user->createToken('crispy_token')->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user,
        ]);
    }

    /**
     * Logout the authenticated user by revoking all tokens.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Refresh the user's authentication token.
     *
     * Revokes all current tokens and issues a new token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('crispy_token')->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
        ]);
    }


}
