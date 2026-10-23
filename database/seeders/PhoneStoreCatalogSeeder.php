<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PhoneStoreCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = [];
        foreach ([['Điện thoại', 'dien-thoai'], ['Phụ kiện', 'phu-kien']] as [$name, $slug]) {
            $categoryIds[$slug] = Category::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => 'Dữ liệu demo PhoneStore.', 'is_active' => true])->id;
        }

        $brandIds = [];
        foreach (['Apple', 'Samsung', 'Xiaomi', 'OPPO', 'vivo', 'realme'] as $name) {
            $brand = Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]);
            $brandIds[$name] = $brand->id;
        }

        $products = [
            ['name' => 'PhoneStore Apple Demo', 'brand' => 'Apple', 'sku' => 'DEMO-APPLE-128', 'price' => 19990000, 'sale_price' => 18990000, 'ram' => '6GB', 'storage' => '128GB'],
            ['name' => 'PhoneStore Galaxy Demo', 'brand' => 'Samsung', 'sku' => 'DEMO-SAMSUNG-256', 'price' => 15990000, 'sale_price' => 14990000, 'ram' => '8GB', 'storage' => '256GB'],
            ['name' => 'PhoneStore Redmi Demo', 'brand' => 'Xiaomi', 'sku' => 'DEMO-XIAOMI-128', 'price' => 7990000, 'sale_price' => null, 'ram' => '8GB', 'storage' => '128GB'],
            ['name' => 'PhoneStore OPPO Demo', 'brand' => 'OPPO', 'sku' => 'DEMO-OPPO-256', 'price' => 10990000, 'sale_price' => null, 'ram' => '8GB', 'storage' => '256GB'],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(['slug' => Str::slug($data['name'])], ['category_id' => $categoryIds['dien-thoai'], 'brand_id' => $brandIds[$data['brand']], 'name' => $data['name'], 'description' => 'Sản phẩm demo dùng để kiểm thử dữ liệu PhoneStore.', 'specifications' => ['demo' => true], 'is_active' => true]);
            ProductVariant::updateOrCreate(['sku' => $data['sku']], ['product_id' => $product->id, 'color' => 'Màu minh họa', 'ram' => $data['ram'], 'storage' => $data['storage'], 'price' => $data['price'], 'sale_price' => $data['sale_price'], 'stock_quantity' => 10, 'is_active' => true]);
        }
    }
}
