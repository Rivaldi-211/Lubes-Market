<?php

namespace App\Http\Requests\Admin;

use App\Models\KelompokKeroyokan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'umkm_id' => ['required', 'integer', 'exists:umkm,id'],
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'kelompok_keroyokan_id' => [
                'nullable',
                'integer',
                'exists:kelompok_keroyokan,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $kelompok = KelompokKeroyokan::find($value);
                        if ($kelompok && (int)$kelompok->kategori_id !== (int)$this->input('kategori_id')) {
                            $fail('Kategori kelompok Keroyokan harus sama dengan kategori produk.');
                        }
                    }
                }
            ],
            'nama_produk' => ['required', 'string', 'max:150'],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'stok_status' => ['required', Rule::in(['Ready', 'Pre-Order', 'Habis'])],
            'stok_jumlah' => ['required', 'integer', 'min:0', 'max:1000000'],
            'estimasi_po_hari' => ['nullable', 'integer', 'min:1', 'max:365'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,jfif,avif', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.file'     => 'File foto yang dipilih tidak valid.',
            'foto.mimes'    => 'Format foto harus berupa JPG, JPEG, PNG, WEBP, atau JFIF.',
            'foto.max'      => 'Ukuran foto maksimal adalah 5 MB.',
            'foto.uploaded' => 'Gagal mengunggah foto. Pastikan ukuran file tidak melebihi batas upload server (maksimal 5 MB).',
        ];
    }
}
