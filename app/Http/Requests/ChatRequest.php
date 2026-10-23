<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['message' => ['required', 'string', 'min:2', 'max:1000']];
    }

    public function messages(): array
    {
        return ['message.required' => 'Vui lòng nhập câu hỏi.', 'message.max' => 'Câu hỏi không được dài quá 1000 ký tự.'];
    }
}
