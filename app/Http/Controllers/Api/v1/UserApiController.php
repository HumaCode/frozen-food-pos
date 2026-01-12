<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    /**
     * Get all users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->withCount('transactions')
            ->orderBy('name', 'asc');

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search by name, username, or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            return $this->formatUser($user);
        });

        return ApiResponse::paginate($users, 'Data pengguna berhasil diambil');
    }

    /**
     * Get user detail
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        $user->loadCount('transactions');

        return ApiResponse::success($this->formatUser($user), 'Detail pengguna berhasil diambil');
    }

    /**
     * Format user data
     *
     * @param User $user
     * @return array
     */
    private function formatUser(User $user): array
    {
        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'username'           => $user->username,
            'email'              => $user->email,
            'phone'              => $user->phone,
            'avatar'             => $user->avatar,
            'avatar_url'         => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'is_active'          => $user->is_active,
            'transactions_count' => $user->transactions_count ?? 0,
            'created_at'         => $user->created_at,
            'updated_at'         => $user->updated_at,
        ];
    }
}
