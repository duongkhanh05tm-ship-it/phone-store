<?php

namespace Tests\Feature;

use App\Models\{Brand, Category, Order, OrderItem, Product, ProductReview, ProductVariant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $category = Category::create(['name' => 'Điện thoại', 'slug' => 'dien-thoai', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $this->product = Product::create(['category_id' => $category->id, 'brand_id' => $brand->id, 'name' => 'Review Phone', 'slug' => 'review-phone', 'is_active' => true]);
        $this->variant = ProductVariant::create(['product_id' => $this->product->id, 'sku' => 'REVIEW-1', 'price' => 10000000, 'stock_quantity' => 3, 'is_active' => true]);
    }

    private function order(User $user, string $status = 'completed'): Order
    {
        $order = Order::create(['user_id' => $user->id, 'customer_name' => $user->name, 'customer_phone' => '0900000000', 'shipping_address' => 'Ha Noi', 'subtotal' => 10000000, 'total_amount' => 10000000, 'payment_method' => 'cod', 'payment_status' => 'paid', 'order_status' => $status]);
        OrderItem::create(['order_id' => $order->id, 'product_variant_id' => $this->variant->id, 'product_name' => $this->product->name, 'sku' => $this->variant->sku, 'unit_price' => 10000000, 'quantity' => 1, 'line_total' => 10000000]);
        return $order;
    }

    public function test_guest_cannot_submit_review(): void
    {
        $this->post(route('products.reviews.store', $this->product), ['rating' => 5, 'comment' => 'Sản phẩm rất tốt'])->assertRedirect(route('login'));
    }

    public function test_only_completed_buyer_can_review_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('products.reviews.store', $this->product), ['rating' => 5, 'comment' => 'Sản phẩm rất tốt'])->assertSessionHasErrors('review');
        $this->order($user);
        $this->actingAs($user)->post(route('products.reviews.store', $this->product), ['rating' => 5, 'comment' => 'Sản phẩm rất tốt'])->assertSessionHas('review_status');
        $this->assertDatabaseHas('product_reviews', ['product_id' => $this->product->id, 'user_id' => $user->id, 'rating' => 5, 'verified_purchase' => true]);
        $this->actingAs($user)->post(route('products.reviews.store', $this->product), ['rating' => 4, 'comment' => 'Đánh giá lần hai'])->assertSessionHasErrors('review');
    }

    public function test_detail_shows_five_star_distribution_and_filter(): void
    {
        $users = User::factory()->count(3)->create();
        foreach ($users as $index => $user) ProductReview::create(['product_id' => $this->product->id, 'user_id' => $user->id, 'rating' => $index + 3, 'comment' => 'Nội dung đánh giá', 'verified_purchase' => true]);
        $this->get(route('products.show', $this->product->slug))->assertOk()->assertSee('3 <span class="text-warning">★</span>', false)->assertSee('4 <span class="text-warning">★</span>', false)->assertSee('5 <span class="text-warning">★</span>', false);
        $this->get(route('products.show', $this->product->slug).'?rating=5')->assertOk()->assertSee('Nội dung đánh giá');
    }
}
