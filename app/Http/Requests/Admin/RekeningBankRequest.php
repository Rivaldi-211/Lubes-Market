<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RekeningBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
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
}
