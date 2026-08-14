<?php
namespace App\Http\Requests\Seller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProductRequest extends FormRequest
{
    public function authorize(): bool { return (bool)$this->user()?->isSeller(); }
    public function rules(): array { return ['kategori_id'=>['required','integer','exists:kategori,id'],'nama_produk'=>['required','string','max:150'],'harga'=>['required','numeric','min:0','max:9999999999'],'stok_status'=>['required',Rule::in(['Ready','Pre-Order','Habis'])],'stok_jumlah'=>['required','integer','min:0','max:1000000'],'estimasi_po_hari'=>['nullable','integer','min:1','max:365'],'deskripsi'=>['nullable','string','max:5000'],'foto'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:2048']]; }
}
