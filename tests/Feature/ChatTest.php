<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $brand = Brand::create(['name' => 'TestBrand', 'slug' => 'testbrand', 'is_active' => true]);
        $category = Category::create(['name' => 'Điện thoại', 'slug' => 'dien-thoai', 'is_active' => true]);
        $product = Product::create(['brand_id' => $brand->id, 'category_id' => $category->id, 'name' => 'Phone Test', 'slug' => 'phone-test', 'is_active' => true, 'specifications' => [['group' => 'Màn hình', 'rows' => [['name' => 'RAM', 'value' => '8GB']]]]]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'TEST-1', 'price' => 4000000, 'sale_price' => null, 'stock_quantity' => 3, 'is_active' => true, 'ram' => '8GB', 'storage' => '128GB']);
        return $product;
    }

    public function test_missing_configuration_uses_real_product_fallback_without_provider_call(): void
    {
        config(['services.ai_chat' => ['enabled' => false, 'api_key' => null, 'base_url' => '', 'model' => '', 'timeout' => 20]]);
        $product = $this->product();
        Http::fake();
        $response = $this->postJson(route('chat.store'), ['message' => 'điện thoại dưới 5 triệu']);
        $response->assertOk()->assertJsonPath('configured', false)->assertSee($product->name);
        Http::assertNothingSent();
    }

    public function test_provider_receives_database_context_and_returns_answer(): void
    {
        config(['services.ai_chat' => ['enabled' => true, 'api_key' => 'secret-key', 'base_url' => 'https://ai.example.test/v1', 'model' => 'test-model', 'timeout' => 20]]);
        $product = $this->product();
        Http::fake(['https://ai.example.test/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'Sản phẩm phù hợp.']]]])]);
        $this->postJson(route('chat.store'), ['message' => 'tư vấn Phone Test'])->assertOk()->assertJsonPath('message', 'Sản phẩm phù hợp.');
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret-key') && str_contains($request['messages'][0]['content'], $product->name) && !str_contains($request['messages'][0]['content'], 'secret-key'));
    }

    public function test_invalid_input_is_rejected_and_provider_error_is_safe(): void
    {
        $this->postJson(route('chat.store'), ['message' => ''])->assertStatus(422);
        config(['services.ai_chat' => ['enabled' => true, 'api_key' => 'secret-key', 'base_url' => 'https://ai.example.test/v1', 'model' => 'test-model', 'timeout' => 20]]);
        $this->product();
        Http::fake(['*' => Http::response(['error' => ['message' => 'quota']], 429)]);
        $this->postJson(route('chat.store'), ['message' => 'xin tư vấn'])->assertStatus(429)->assertJsonMissing(['message' => 'secret-key']);
    }
}
