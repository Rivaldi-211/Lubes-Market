<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class RekeningBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSeller();
    }

    public function rules(): array
    {
        return [
            'nama_bank' => ['required', 'string', 'max:100'],
            'nomor_rekening' => ['required', 'string', 'max:50'],
            'atas_nama' => ['required', 'string', 'max:150'],
            'aktif' => ['nullable', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_bank.required' => 'Nama bank wajib diisi (Contoh: Bank BRI, Bank BCA).',
            'nomor_rekening.required' => 'Nomor rekening wajib diisi.',
            'atas_nama.required' => 'Nama pemilik rekening (atas nama) wajib diisi.',
        ];
    }
}
