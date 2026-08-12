<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UserStatusRequest extends FormRequest
{
    public function authorize(): bool { return (bool)$this->user()?->isAdmin(); }
    public function rules(): array { return ['status'=>['required',Rule::in(['aktif','nonaktif'])]]; }
}
