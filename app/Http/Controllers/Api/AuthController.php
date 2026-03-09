<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => $data['password'], // auto-hashed by User model's 'hashed' cast
            'is_active' => true,
        ]);

        // Ensure baseline roles exist in fresh databases before assigning one.
        $guardName = config('auth.defaults.guard', 'web');
        Role::findOrCreate('admin', $guardName);
        Role::findOrCreate('vendor', $guardName);
        Role::findOrCreate('customer', $guardName);

        $adminEmail = strtolower(trim((string) env('ADMIN_EMAIL', '')));
        $roleToAssign = $adminEmail !== '' && strtolower($user->email) === $adminEmail
            ? 'admin'
            : 'customer';

        $user->assignRole($roleToAssign);
        $user->load('roles');

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user'  => $this->formatUser($user),
            'token' => $token,
        ], 'Registered successfully.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error('Your account has been deactivated.', 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user'  => $this->formatUser($user),
            'token' => $token,
        ], 'Logged in successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return ApiResponse::success(['user' => $this->formatUser($user)]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'is_active'  => $user->is_active,
            'roles'      => $user->relationLoaded('roles') ? $user->getRoleNames() : [],
            'created_at' => $user->created_at,
        ];
    }
}
