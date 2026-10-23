<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('product')?->id;
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($id)],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'description' => ['nullable', 'string', 'max:10000'],
            'specifications' => ['nullable'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name.required'=>'Vui lòng nhập tên sản phẩm.','slug.required'=>'Vui lòng nhập slug.','slug.unique'=>'Slug đã tồn tại.','category_id.exists'=>'Danh mục không hợp lệ.','brand_id.exists'=>'Thương hiệu không hợp lệ.'];
    }
}