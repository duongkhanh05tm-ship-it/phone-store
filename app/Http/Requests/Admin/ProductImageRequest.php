<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
class ProductImageRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['product_variant_id'=>['nullable','integer'],'images'=>'required|array|min:1','images.*'=>'required|image|mimes:jpg,jpeg,png,webp|max:4096']; } public function messages(): array { return ['images.required'=>'Vui lòng chọn ảnh.','images.*.image'=>'File phải là hình ảnh.','images.*.mimes'=>'Chỉ nhận JPG, JPEG, PNG hoặc WEBP.','images.*.max'=>'Mỗi ảnh tối đa 4MB.']; } }