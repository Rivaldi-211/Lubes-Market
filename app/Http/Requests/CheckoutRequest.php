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
            'metode_pembayaran'=>['required',Rule::in(['COD','Transfer','QRIS','Savala'])],
            'alamat_pengiriman'=>['required','string','max:255'],
            'no_hp_pembeli'=>['required','string','max:20'],
            'catatan'=>['nullable','string','max:255'],
        ];
    }
    public function messages(): array { return ['alamat_pengiriman.required'=>'Alamat atau titik pengambilan wajib diisi.','no_hp_pembeli.required'=>'Nomor HP pembeli wajib diisi.']; }
}
