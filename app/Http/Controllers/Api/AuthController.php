<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
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

        $adminEmail = strtolower(trim((string) config('app.admin_email', '')));
        $roleToAssign = $adminEmail !== '' && strtolower($user->email) === $adminEmail
            ? 'admin'
            : 'customer';

        $user->assignRole($roleToAssign);
        $user->load('roles');

        $user->sendEmailVerificationNotification();

        return ApiResponse::success([
            'requires_verification' => true,
            'email' => $user->email,
        ], 'Registered successfully. Please verify your email.', 201);
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

        if (! $user->hasVerifiedEmail()) {
            return ApiResponse::error(
                'Please verify your email before logging in.',
                403,
                ['email' => ['Email address is not verified.']]
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user'  => $this->formatUser($user),
            'token' => $token,
        ], 'Logged in successfully.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink([
            'email' => $data['email'],
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return ApiResponse::success(null, __($status));
        }

        // Keep response generic to avoid account enumeration.
        return ApiResponse::success(null, 'If that email exists, a reset link has been sent.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ],
            function (User $user) use ($data): void {
                $user->forceFill([
                    'password' => $data['password'],
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(__($status), 422);
        }

        return ApiResponse::success(null, __($status));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        try {
            DB::transaction(function () use ($user): void {
                // Revoke all active tokens first, then remove the account.
                $user->tokens()->delete();
                $user->delete();
            });

            return ApiResponse::success(null, 'Account deleted successfully.');
        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Unable to delete account at the moment. Please contact support.',
                422
            );
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return ApiResponse::success(['user' => $this->formatUser($user)]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();

        // Do not leak account existence or verification status.
        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return ApiResponse::success(null, 'If this email exists, a verification link has been sent.');
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        if (! URL::hasValidSignature($request)) {
            return redirect($this->verificationRedirectUrl('failed'));
        }

        $user = User::find($id);

        if (! $user) {
            return redirect($this->verificationRedirectUrl('failed'));
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect($this->verificationRedirectUrl('failed'));
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect($this->verificationRedirectUrl('success'));
    }

    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'is_active'  => $user->is_active,
            'email_verified_at' => $user->email_verified_at,
            'is_email_verified' => $user->hasVerifiedEmail(),
            'roles'      => $user->relationLoaded('roles') ? $user->getRoleNames() : [],
            'created_at' => $user->created_at,
        ];
    }

    private function verificationRedirectUrl(string $status): string
    {
        $frontend = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');

        return "{$frontend}/login?verify={$status}";
    }
}
