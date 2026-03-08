<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $sortBy  = in_array($request->sort_by, ['name', 'email', 'created_at']) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->get('per_page', 15), 100);
        $users   = $query->with('roles')->paginate($perPage);

        return response()->json($users->through(fn ($user) => $this->formatUser($user)));
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        $stats = [
            'total_orders'  => $user->orders()->count(),
            'total_spent'   => (float) $user->orders()->where('payment_status', 'paid')->sum('total'),
            'total_reviews' => $user->reviews()->count(),
        ];

        return response()->json(array_merge($this->formatUser($user), ['stats' => $stats]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($data);

        return response()->json($this->formatUser($user->fresh()->load('roles')));
    }

    public function toggleActive(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'message'   => 'User status updated.',
            'is_active' => $user->is_active,
        ]);
    }

    public function changeRole(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => 'required|string|in:admin,vendor,customer',
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'User role updated.',
            'roles'   => $user->getRoleNames(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
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
            'updated_at' => $user->updated_at,
        ];
    }
}
