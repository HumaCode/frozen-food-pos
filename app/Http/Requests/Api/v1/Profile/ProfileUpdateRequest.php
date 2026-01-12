<?php

namespace App\Http\Requests\Api\v1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
        $userId = $this->user()->id;

        return [
            'name'      => 'required|string|max:255',
            'username'  => 'required|string|max:255|alpha_dash|unique:users,username,' . $userId,
            'email'     => 'required|string|email|max:255|unique:users,email,' . $userId,
            'phone'     => 'nullable|string|max:20',
            'avatar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'         => 'Nama wajib diisi',
            'name.max'              => 'Nama maksimal 255 karakter',
            'username.required'     => 'Username wajib diisi',
            'username.unique'       => 'Username sudah digunakan',
            'username.alpha_dash'   => 'Username hanya boleh huruf, angka, dash dan underscore',
            'email.required'        => 'Email wajib diisi',
            'email.email'           => 'Format email tidak valid',
            'email.unique'          => 'Email sudah digunakan',
            'avatar.image'          => 'Avatar harus berupa gambar',
            'avatar.mimes'          => 'Avatar harus berformat jpg, jpeg, png, atau webp',
            'avatar.max'            => 'Avatar maksimal 2MB',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            if ($this->username === 'admin' && $this->user()->id !== 1) {
                $validator->errors()->add('username', 'Username admin tidak diperbolehkan');
            }
        });
    }
}
