<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $imageRequired = $this->isMethod('post') ? 'required' : 'nullable';
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => [$imageRequired, 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'link_url' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && !str_starts_with($value, '/') && !filter_var($value, FILTER_VALIDATE_URL)) $fail('Liên kết phải là đường dẫn nội bộ hoặc URL hợp lệ.');
                if (is_string($value) && preg_match('/^https?:\/\//i', $value) === 0 && $value !== '' && !str_starts_with($value, '/')) $fail('Liên kết không an toàn.');
            }],
            'position' => ['required', Rule::in(['main', 'side'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
