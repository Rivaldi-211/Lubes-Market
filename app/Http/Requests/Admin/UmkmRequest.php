<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UmkmRequest extends FormRequest
{
    public function authorize(): bool { return (bool)$this->user()?->isAdmin(); }
    public function rules(): array
    {
        $creating=$this->isMethod('post');
        return [
            'nama_umkm'=>['required','string','max:150'],'pemilik'=>['required','string','max:100'],'alamat'=>['nullable','string','max:255'],'no_hp'=>['nullable','string','max:20'],'deskripsi'=>['nullable','string','max:3000'],'status'=>['required',Rule::in(['aktif','nonaktif'])],'foto'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'user_id'=>$creating?['nullable','integer',Rule::exists('users','id')->where(fn($q)=>$q->where('role','penjual'))]:['nullable'],
            'nama_lengkap'=>$creating?['required_without:user_id','nullable','string','max:100']:['nullable'],
            'username'=>$creating?['required_without:user_id','nullable','alpha_dash','max:50','unique:users,username']:['nullable'],
            'email'=>$creating?['nullable','email','max:150','unique:users,email']:['nullable'],
            'password'=>$creating?['required_without:user_id','nullable','string','min:8','confirmed']:['nullable'],
        ];
    }
}
