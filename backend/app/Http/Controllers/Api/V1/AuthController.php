<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Handle user login and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
                'errors' => [
                    'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
            'message' => 'Login berhasil',
        ], Response::HTTP_OK);
    }

    /**
     * Revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'data' => null,
            'message' => 'Logout berhasil',
        ], Response::HTTP_OK);
    }

    /**
     * Get current user and assigned location summary.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('workLocation');
        $location = $user->workLocation;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'location' => $location && $location->is_active ? [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'radius_meters' => (int) $location->radius_meters,
                    'max_accuracy_meters' => (int) $location->max_accuracy_meters,
                ] : null,
            ],
            'message' => 'Success',
        ], Response::HTTP_OK);
    }
}
