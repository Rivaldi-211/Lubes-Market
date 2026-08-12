<?php
namespace App\Http\Requests\Seller;
use Illuminate\Foundation\Http\FormRequest;
class ProfileRequest extends FormRequest
{
    public function authorize(): bool { return (bool)$this->user()?->isSeller(); }
    public function rules(): array { return ['nama_umkm'=>['required','string','max:150'],'pemilik'=>['required','string','max:100'],'alamat'=>['nullable','string','max:255'],'no_hp'=>['nullable','string','max:20'],'deskripsi'=>['nullable','string','max:3000'],'foto'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:2048']]; }
}
