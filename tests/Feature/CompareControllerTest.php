<?php

namespace Tests\Feature;

use App\Models\{Brand, Category, Product, ProductVariant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareControllerTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, array $product = [], array $variant = []): Product
    {
        $brand = Brand::create(['name' => $name.' Brand', 'slug' => strtolower($name).'-brand', 'is_active' => true]);
        $category = Category::create(['name' => $name.' Category', 'slug' => strtolower($name).'-category', 'is_active' => true]);
        $item = Product::create(array_merge(['brand_id' => $brand->id, 'category_id' => $category->id, 'name' => $name, 'slug' => strtolower($name), 'is_active' => true, 'specifications' => []], $product));
        ProductVariant::create(array_merge(['product_id' => $item->id, 'sku' => $name.'-1', 'ram' => '8GB', 'storage' => '256GB', 'price' => 10000000, 'sale_price' => null, 'stock_quantity' => 5, 'is_active' => true], $variant));
        return $item;
    }

    public function test_compare_accepts_two_or_three_valid_products_and_deduplicates(): void
    {
        $a = $this->product('Phone A'); $b = $this->product('Phone B'); $c = $this->product('Phone C');
        $this->get('/compare?ids='.$a->id.','.$b->id.','.$b->id.','.$c->id)->assertOk()->assertSee('Phone A')->assertSee('Phone B')->assertSee('Phone C');
    }

    public function test_compare_ignores_missing_inactive_and_limits_to_three(): void
    {
        $products = collect(range(1, 4))->map(fn ($i) => $this->product('Phone '.$i));
        $inactive = $this->product('Inactive', ['is_active' => false]);
        $html = $this->get('/compare?ids='.$products->take(3)->pluck('id')->push($inactive->id)->implode(','))->assertOk();
        $html->assertSee('Phone 1')->assertSee('Phone 2')->assertSee('Phone 3')->assertDontSee('Inactive');
        $this->get('/compare?ids=999999')->assertOk()->assertSee('ít nhất 2 sản phẩm');
    }

    public function test_variant_must_be_active_and_belong_to_product_and_price_comes_from_database(): void
    {
        $a = $this->product('Phone A', [], ['price' => 12000000, 'sale_price' => 9900000]);
        ProductVariant::create(['product_id' => $a->id, 'sku' => 'second', 'price' => 13000000, 'stock_quantity' => 1, 'is_active' => true]);
        $other = $this->product('Phone B', [], ['price' => 8000000]);
        $inactive = ProductVariant::create(['product_id' => $a->id, 'sku' => 'inactive', 'price' => 500000, 'stock_quantity' => 1, 'is_active' => false]);
        $valid = $a->variants()->where('is_active', true)->first();
        $this->get('/compare?ids='.$a->id.'&variants%5B'.$a->id.'%5D='.$valid->id)->assertOk()->assertSee('Phone A');
        $response = $this->get('/compare?ids='.$a->id.'&variants['.$a->id.']='.$other->variants->first()->id);
        $response->assertOk()->assertSee('Phone A');
        $this->get('/compare?ids='.$a->id.'&variants%5B'.$a->id.'%5D='.$inactive->id)->assertOk()->assertDontSee('500.000đ');
    }

    public function test_empty_specifications_render_without_fabricated_values(): void
    {
        $a = $this->product('Phone Empty', ['specifications' => null]); $b = $this->product('Phone Other');
        $this->get('/compare?ids='.$a->id.','.$b->id)->assertOk()->assertSee('Thông số kỹ thuật')->assertSee('Phone Empty');
    }
}
