<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginApiRequest;
use App\Http\Requests\RegisterApiRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'username'      => $user->username,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'avatar'        => $user->avatar,
                'is_active'     => $user->is_active,
                'created_at'    => $user->created_at,
            ],
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
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'username'   => $user->username,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'avatar'     => $user->avatar,
                'is_active'  => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 'Registrasi berhasil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
