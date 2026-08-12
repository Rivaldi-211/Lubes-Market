<?php
namespace App\Http\Requests\Buyer;
use Illuminate\Foundation\Http\FormRequest;
class PaymentProofRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->isBuyer(); }
    public function rules(): array { return ['bukti_pembayaran'=>['required','image','mimes:jpg,jpeg,png,webp','max:2048']]; }
    public function messages(): array { return ['bukti_pembayaran.image'=>'Bukti pembayaran harus berupa gambar.','bukti_pembayaran.max'=>'Ukuran bukti pembayaran maksimal 2 MB.']; }
}
