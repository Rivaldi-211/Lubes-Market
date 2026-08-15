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
            'nama_lengkap'    => ['required', 'string', 'max:100'],
            'username'        => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'email'           => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'no_hp'           => ['nullable', 'string', 'max:20'],
            'alamat_utama'    => ['nullable', 'string', 'max:500'],
            'zona_pengiriman' => ['nullable', 'string', 'max:100'],
            'jenis_kelamin'   => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
            'tanggal_lahir'   => ['nullable', 'date', 'before:today'],
            'foto_profil'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
            'foto_profil.image'     => 'Berkas foto profil harus berupa gambar.',
            'foto_profil.mimes'     => 'Format foto profil yang didukung: jpg, jpeg, png, webp.',
            'foto_profil.max'       => 'Ukuran foto profil maksimal 2 MB.',
            'tanggal_lahir.before'  => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}
