<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSeller();
    }

    public function rules(): array
    {
        return [
            'nama_umkm' => ['required', 'string', 'max:150'],
            'pemilik'   => ['required', 'string', 'max:100'],
            'alamat'    => ['nullable', 'string', 'max:255'],
            'no_hp'     => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:3000'],
            'foto'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_umkm.required' => 'Nama UMKM wajib diisi.',
            'nama_umkm.max'      => 'Nama UMKM maksimal :max karakter.',
            'pemilik.required'   => 'Nama pemilik wajib diisi.',
            'pemilik.max'        => 'Nama pemilik maksimal :max karakter.',
            'alamat.max'         => 'Alamat maksimal :max karakter.',
            'no_hp.max'          => 'Nomor HP maksimal :max karakter.',
            'deskripsi.max'      => 'Deskripsi maksimal :max karakter.',
            'foto.image'         => 'File foto harus berupa gambar.',
            'foto.mimes'         => 'Format foto harus berupa JPG, PNG, atau WebP.',
            'foto.max'           => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
