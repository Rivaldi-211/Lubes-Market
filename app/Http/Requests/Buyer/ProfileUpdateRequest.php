<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isBuyer();
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'username'     => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'email'        => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'no_hp'        => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal :max karakter.',
            'username.required'     => 'Username wajib diisi.',
            'username.alpha_dash'   => 'Username hanya boleh berisi huruf, angka, strip (-), dan garis bawah (_).',
            'username.max'          => 'Username maksimal :max karakter.',
            'username.unique'       => 'Username ini sudah digunakan oleh akun lain.',
            'email.email'           => 'Format alamat email tidak valid.',
            'email.unique'          => 'Email ini sudah terdaftar pada akun lain.',
            'no_hp.max'             => 'Nomor HP maksimal :max karakter.',
        ];
    }
}
