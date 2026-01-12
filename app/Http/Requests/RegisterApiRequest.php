<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Nama wajib diisi',
            'name.max'             => 'Nama maksimal 255 karakter',

            'username.required'   => 'Username wajib diisi',
            'username.unique'     => 'Username sudah digunakan',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, dash dan underscore',

            'email.required'      => 'Email wajib diisi',
            'email.email'         => 'Format email tidak valid',
            'email.unique'        => 'Email sudah terdaftar',

            'password.required'   => 'Password wajib diisi',
            'password.min'        => 'Password minimal 6 karakter',
            'password.confirmed'  => 'Konfirmasi password tidak cocok',
        ];
    }
}
