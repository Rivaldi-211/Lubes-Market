<?php
namespace App\Http\Requests\Seller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool { return (bool)$this->user()?->isSeller(); }
    public function rules(): array { return ['status'=>['required',Rule::in(['Menunggu','Diproses','Selesai','Dibatalkan'])]]; }
}
