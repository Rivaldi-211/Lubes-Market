<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isBuyer() === true; }
    public function rules(): array
    {
        return [
            'metode_pembayaran'=>['required',Rule::in(['COD','Transfer','QRIS'])],
            'rekening_bank_id' => [
                Rule::requiredIf(fn () => $this->input('metode_pembayaran') === 'Transfer'),
                'nullable',
                'integer',
                Rule::exists('rekening_bank', 'id')->where('aktif', true),
            ],
            'alamat_pengiriman'=>['required','string','max:255'],
            'zona_pengiriman'=>['nullable','string', Rule::exists('zona_pengiriman', 'nama_zona')->where('aktif', true)],
            'opsi_packing'=>['nullable','string'],
            'no_hp_pembeli'=>['required','string','max:20'],
            'catatan'=>['nullable','string','max:255'],
        ];
    }
    public function messages(): array { return ['alamat_pengiriman.required'=>'Alamat atau titik pengambilan wajib diisi.','no_hp_pembeli.required'=>'Nomor HP pembeli wajib diisi.','rekening_bank_id.required'=>'Silakan pilih salah satu rekening bank platform tujuan transfer.','rekening_bank_id.exists'=>'Rekening bank yang dipilih tidak valid atau sedang tidak aktif.']; }
}
