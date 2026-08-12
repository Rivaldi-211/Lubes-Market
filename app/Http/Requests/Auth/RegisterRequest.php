<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'nama_lengkap'=>['required','string','max:100'],
            'username'=>['required','alpha_dash','max:50','unique:users,username'],
            'email'=>['nullable','email','max:100','unique:users,email'],
            'no_hp'=>['nullable','string','max:20'],
            'password'=>['required','string','min:8','confirmed'],
            'role'=>['required',Rule::in(['pembeli','penjual'])],
            'nama_umkm'=>[Rule::requiredIf(fn()=> $this->input('role')==='penjual'),'nullable','string','max:150'],
            'alamat'=>['nullable','string','max:255'],
        ];
    }
}
