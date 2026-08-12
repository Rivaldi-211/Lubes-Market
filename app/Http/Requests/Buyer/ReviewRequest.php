<?php
namespace App\Http\Requests\Buyer;
use Illuminate\Foundation\Http\FormRequest;
class ReviewRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->isBuyer(); }
    public function rules(): array { return ['rating'=>['required','integer','between:1,5'],'komentar'=>['nullable','string','max:1000']]; }
}
