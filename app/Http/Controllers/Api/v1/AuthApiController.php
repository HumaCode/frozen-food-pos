<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Profile\ProfileUpdateRequest;
use App\Http\Requests\LoginApiRequest;
use App\Http\Requests\RegisterApiRequest;
use App\Http\Resources\Api\v1\AuthApiResource;
use App\Http\Resources\Api\v1\LoginResource;
use App\Http\Resources\Api\v1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthApiController extends Controller
{
    /**
     * Login user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(LoginApiRequest $request): JsonResponse
    {
        // Cek apakah login menggunakan email atau username
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Cari user
        $user = User::where($loginField, $request->login)->first();

        // Validasi user & password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::unauthorized('Email/username atau password salah');
        }

        // Cek apakah user aktif
        if (!$user->is_active) {
            return ApiResponse::forbidden('Akun Anda tidak aktif. Silakan hubungi admin.');
        }

        // Hapus token lama (optional: untuk single device login)
        // $user->tokens()->delete();

        // Generate token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user'          => new AuthApiResource($user),
            'token'         => $token,
            'token_type'    => 'Bearer',
        ], 'Login berhasil');
    }

    /**
     * Register new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(RegisterApiRequest $request): JsonResponse
    {
        $user = User::create([
            'name'      => $request->name,
            'username'  => $request->username,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::created([
            'user'       => new AuthApiResource($user),
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 'Registrasi berhasil');
    }

    /**
     * Logout user (revoke current token)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Hapus token saat ini
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout berhasil');
    }

    /**
     * Get current authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            new AuthApiResource($request->user()),
        ], 'Data user berhasil diambil');
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        // Handle avatar upload
        $avatarPath = $user->avatar;

        if ($request->hasFile('avatar')) {
            // Hapus avatar lama jika ada
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Simpan avatar baru
            $avatarPath = $request->file('avatar')->store('users', 'public');
        }

        // Update user
        $user->update([
            'name'      => $request->name,
            'username'  => $request->username,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'avatar'    => $avatarPath,
        ]);

        return ApiResponse::success(
            new UserResource($user),
            'Profil berhasil diperbarui'
        );
    }

    /**
     * Update user password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validasi input
        $validator = Validator::make($request->all(), [
            'current_password'  => 'required|string',
            'password'          => 'required|string|min:6|confirmed',
        ], [
            'current_password.required'     => 'Password saat ini wajib diisi',
            'password.required'             => 'Password baru wajib diisi',
            'password.min'                  => 'Password baru minimal 6 karakter',
            'password.confirmed'            => 'Konfirmasi password tidak cocok',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        // Cek password saat ini
        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::validationError([
                'current_password' => ['Password saat ini salah'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return ApiResponse::success(null, 'Password berhasil diperbarui');
    }
}
