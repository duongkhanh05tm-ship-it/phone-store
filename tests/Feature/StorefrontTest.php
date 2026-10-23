<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(array $product = [], array $variant = [], array $brand = [], array $category = []): array
    {
        static $counter = 0;
        $counter++;
        $brandModel = Brand::create(array_merge(['name' => 'Apple '.$counter, 'slug' => 'apple-'.$counter, 'is_active' => true], $brand));
        $categoryModel = Category::create(array_merge(['name' => 'Điện thoại '.$counter, 'slug' => 'dien-thoai-'.$counter, 'is_active' => true], $category));
        $variant['sku'] ??= 'SKU-'.$counter;
        $productModel = Product::create(array_merge([
            'brand_id' => $brandModel->id, 'category_id' => $categoryModel->id,
            'name' => 'iPhone Test', 'slug' => 'iphone-test', 'is_active' => true,
        ], $product));
        ProductVariant::create(array_merge([
            'product_id' => $productModel->id, 'sku' => 'SKU-'.$productModel->id,
            'price' => 10000000, 'sale_price' => null, 'stock_quantity' => 5, 'is_active' => true,
        ], $variant));
        return [$productModel, $brandModel, $categoryModel];
    }

    public function test_home_shows_only_sellable_active_products(): void
    {
        [$active] = $this->catalog(['name' => 'Active Phone', 'slug' => 'active-phone']);
        [$inactive] = $this->catalog(['name' => 'Inactive Phone', 'slug' => 'inactive-phone', 'is_active' => false]);
        [$noVariant] = $this->catalog(['name' => 'No Active Variant', 'slug' => 'no-active-variant'], ['is_active' => false]);
        $this->get('/')->assertOk()->assertSee('Active Phone')->assertDontSee('Inactive Phone')->assertDontSee('No Active Variant');
    }

    public function test_search_brand_category_and_hidden_catalog_filters_work(): void
    {
        [$phone, $brand, $category] = $this->catalog(['name' => 'Galaxy Search', 'slug' => 'galaxy-search']);
        $this->catalog(['name' => 'Other Phone', 'slug' => 'other-phone'], [], ['name' => 'Samsung', 'slug' => 'samsung'], ['name' => 'Tablet', 'slug' => 'tablet']);
        $this->get('/products?q=Galaxy')->assertOk()->assertSee('Galaxy Search')->assertDontSee('Other Phone');
        $this->get('/products?brand_id='.$brand->id)->assertSee('Galaxy Search');
        $this->get('/products?category_id='.$category->id)->assertSee('Galaxy Search');

        [$hiddenBrand] = $this->catalog(['name' => 'Hidden Brand Phone', 'slug' => 'hidden-brand-phone'], [], ['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
        [$hiddenCategory] = $this->catalog(['name' => 'Hidden Category Phone', 'slug' => 'hidden-category-phone'], [], [], ['name' => 'Hidden Cat', 'slug' => 'hidden-cat', 'is_active' => false]);
        $this->get('/products')->assertDontSee($hiddenBrand->name)->assertDontSee($hiddenCategory->name);
    }

    public function test_price_sort_uses_lowest_effective_sale_price_and_paginates(): void
    {
        $this->catalog(['name' => 'Expensive', 'slug' => 'expensive'], ['price' => 30000000]);
        $this->catalog(['name' => 'Cheap Sale', 'slug' => 'cheap-sale'], ['price' => 20000000, 'sale_price' => 5000000]);
        for ($i = 0; $i < 12; $i++) $this->catalog(['name' => 'Phone '.$i, 'slug' => 'phone-'.$i]);
        $response = $this->get('/products?sort=price_asc');
        $response->assertOk()->assertSee('Cheap Sale')->assertSee('page=2');
        $this->get('/products?sort=price_desc')->assertOk()->assertSee('Expensive');
        $this->get('/products?sort=price_asc&page=2')->assertOk()->assertSee('Expensive');
    }

    public function test_detail_returns_404_for_inactive_product_and_shows_variant_price_stock(): void
    {
        [$product] = $this->catalog([], ['price' => 12000000, 'sale_price' => 9990000, 'stock_quantity' => 7]);
        $this->get('/products/'.$product->slug)->assertOk()->assertSee('9.990.000đ')->assertSee('data-stock="7"', false)->assertSee('data-price="9.990.000đ"', false);
        [$inactive] = $this->catalog(['slug' => 'gone', 'is_active' => false]);
        $this->get('/products/'.$inactive->slug)->assertNotFound();
    }

    public function test_product_suggestions_filter_catalog_and_limit_results(): void
    {
        $this->catalog(['name' => 'Brand Match Phone', 'slug' => 'brand-match-phone'], [], ['name' => 'Galaxy Brand', 'slug' => 'galaxy-brand']);
        $this->catalog(['name' => 'Inactive Suggestion', 'slug' => 'inactive-suggestion', 'is_active' => false]);
        $this->catalog(['name' => 'Hidden Suggestion', 'slug' => 'hidden-suggestion'], [], [], ['is_active' => false]);
        for ($i = 0; $i < 7; $i++) $this->catalog(['name' => 'Galaxy '.$i, 'slug' => 'galaxy-'.$i]);
        $brandResponse = $this->getJson(route('products.suggest', ['q' => 'Galaxy Brand']));
        $brandResponse->assertOk()->assertJsonFragment(['name' => 'Brand Match Phone']);
        $response = $this->getJson(route('products.suggest', ['q' => 'Galaxy']));
        $response->assertOk()->assertJsonCount(6, 'data');
        $response->assertJsonMissing(['name' => 'Inactive Suggestion'])->assertJsonMissing(['name' => 'Hidden Suggestion']);
        $this->getJson(route('products.suggest', ['q' => 'G']))->assertJsonPath('data', []);
    }

    public function test_home_brand_filter_returns_partial_and_respects_active_catalog(): void
    {
        [$phone, $brand] = $this->catalog(['name' => 'Apple Filter Phone', 'slug' => 'apple-filter-phone']);
        [$hidden] = $this->catalog(['name' => 'Inactive Filter Phone', 'slug' => 'inactive-filter-phone'], [], [], ['is_active' => false]);
        $this->get(route('products.filter-home', ['brand_id' => $brand->id]))->assertOk()->assertSee('Apple Filter Phone')->assertDontSee('Inactive Filter Phone');
        $this->get('/?brand_id='.$brand->id)->assertOk()->assertSee('Apple Filter Phone');
    }

    public function test_product_detail_renders_database_sections_and_safe_purchase_controls(): void
    {
        [$product] = $this->catalog([
            'name' => 'Detailed Phone',
            'slug' => 'detailed-phone',
            'description' => 'Mô tả lấy từ cơ sở dữ liệu.',
            'specifications' => [['group' => 'Màn hình', 'rows' => [['name' => 'Kích thước', 'value' => '6.1 inch'], ['name' => 'Độ phân giải', 'value' => 'FHD+']]]],
        ], ['ram' => '8GB', 'storage' => '256GB', 'color' => 'Đen', 'sale_price' => 9000000]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertSee('Mô tả lấy từ cơ sở dữ liệu.')
            ->assertSee('ĐẶC ĐIỂM NỔI BẬT')
            ->assertSee('THÔNG SỐ KỸ THUẬT')
            ->assertSee('Thêm vào giỏ hàng')
            ->assertSee('product-purchase-form', false)
            ->assertSee('sticky-purchase-bar', false)
            ->assertSee('data-configuration="8GB · 256GB"', false)
            ->assertSee('data-color="Đen"', false);
    }

    public function test_gallery_payload_separates_common_and_variant_images_with_color_fallback(): void
    {
        [$product] = $this->catalog(['name' => 'Gallery Phone', 'slug' => 'gallery-phone'], ['color' => 'Đen', 'ram' => '8GB', 'storage' => '128GB']);
        $black = $product->variants()->first();
        $sameColor = ProductVariant::create(['product_id' => $product->id, 'sku' => 'GALLERY-256', 'color' => 'Đen', 'ram' => '8GB', 'storage' => '256GB', 'price' => 11000000, 'stock_quantity' => 2, 'is_active' => true]);
        ProductImage::create(['product_id' => $product->id, 'product_variant_id' => $sameColor->id, 'image_path' => 'products/black.jpg', 'sort_order' => 0]);
        ProductImage::create(['product_id' => $product->id, 'image_path' => 'products/common.jpg', 'sort_order' => 1]);
        $response = $this->get('/products/'.$product->slug)->assertOk();
        $response->assertSee('black.jpg')->assertSee('common.jpg');
        $this->assertStringNotContainsString('products/foreign.jpg', $response->getContent());
    }

    public function test_products_without_images_render_local_placeholder(): void
    {
        [$product] = $this->catalog(['name' => 'No Image Phone', 'slug' => 'no-image-phone']);
        $this->get('/products/'.$product->slug)->assertOk()->assertSee('images/phonestore-placeholder.svg');
    }
}
