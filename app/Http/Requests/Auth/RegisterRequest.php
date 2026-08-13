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
    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal :max karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip (-), dan underscore (_). Tidak boleh menggunakan spasi.',
            'username.max' => 'Username maksimal :max karakter.',
            'username.unique' => 'Username ini sudah digunakan.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Silakan pilih peran akun.',
            'role.in' => 'Peran akun tidak valid.',
            'nama_umkm.required' => 'Nama UMKM wajib diisi jika mendaftar sebagai penjual.',
            'nama_umkm.max' => 'Nama UMKM maksimal :max karakter.',
        ];
    }
}
