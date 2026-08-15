<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class AjukanPencairanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isSeller() && $this->user()?->umkm);
    }

    public function rules(): array
    {
        return [
            'rekening_bank_id' => ['required', 'integer', 'exists:rekening_bank,id'],
            'catatan'          => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rekening_bank_id.required' => 'Pilih rekening bank tujuan pencairan.',
            'rekening_bank_id.integer'  => 'ID rekening bank tidak valid.',
            'rekening_bank_id.exists'   => 'Rekening bank yang dipilih tidak ditemukan.',
            'catatan.max'               => 'Catatan pengajuan maksimal :max karakter.',
        ];
    }
}
