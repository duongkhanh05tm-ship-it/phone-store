<?php

namespace Tests\Feature\Admin;

use App\Models\{Brand, Category, Product, Promotion, ProductVariant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Điện thoại', 'slug' => 'dien-thoai', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $this->product = Product::create(['category_id' => $category->id, 'brand_id' => $brand->id, 'name' => 'Phone', 'slug' => 'phone', 'is_active' => true]);
        ProductVariant::create(['product_id' => $this->product->id, 'sku' => 'PHONE-1', 'price' => 10000000, 'sale_price' => 9000000, 'stock_quantity' => 3, 'is_active' => true]);
    }

    private function admin() { return $this->actingAs($this->admin); }

    private function data(array $overrides = []): array
    {
        return array_merge(['product_id' => $this->product->id, 'title' => 'Quà tặng', 'content' => 'Thông tin ưu đãi', 'starts_at' => now()->subDay()->format('Y-m-d H:i'), 'ends_at' => now()->addDay()->format('Y-m-d H:i'), 'sort_order' => 1, 'is_active' => 1], $overrides);
    }

    public function test_regular_user_cannot_manage_promotions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.promotions.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.promotions.store'), $this->data())->assertForbidden();
    }

    public function test_admin_can_create_edit_toggle_and_delete_promotion(): void
    {
        $this->admin()->get(route('admin.promotions.index'))->assertOk();
        $this->admin()->post(route('admin.promotions.store'), $this->data())->assertRedirect();
        $promotion = Promotion::firstOrFail();
        $this->assertDatabaseHas('promotions', ['id' => $promotion->id, 'title' => 'Quà tặng']);
        $this->admin()->put(route('admin.promotions.update', $promotion), $this->data(['title' => 'Ưu đãi mới']))->assertRedirect();
        $this->assertDatabaseHas('promotions', ['id' => $promotion->id, 'title' => 'Ưu đãi mới']);
        $this->admin()->patch(route('admin.promotions.status', $promotion))->assertRedirect();
        $this->assertDatabaseHas('promotions', ['id' => $promotion->id, 'is_active' => false]);
        $this->admin()->delete(route('admin.promotions.destroy', $promotion))->assertRedirect();
        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_storefront_only_shows_current_active_product_promotions(): void
    {
        Promotion::create($this->data(['title' => 'Đang hiện']));
        Promotion::create($this->data(['title' => 'Đã tắt', 'is_active' => false]));
        Promotion::create($this->data(['title' => 'Chưa bắt đầu', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]));
        Promotion::create($this->data(['title' => 'Đã hết hạn', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]));
        $other = Product::create(['category_id' => $this->product->category_id, 'brand_id' => $this->product->brand_id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        ProductVariant::create(['product_id' => $other->id, 'sku' => 'OTHER-1', 'price' => 5000000, 'stock_quantity' => 1, 'is_active' => true]);
        Promotion::create($this->data(['product_id' => $other->id, 'title' => 'Sản phẩm khác']));

        $this->get(route('products.show', $this->product->slug))->assertOk()->assertSee('Đang hiện')->assertDontSee('Đã tắt')->assertDontSee('Chưa bắt đầu')->assertDontSee('Đã hết hạn')->assertDontSee('Sản phẩm khác')->assertSee('"discount":10', false);
        $this->get(route('products.show', $other->slug))->assertOk()->assertDontSee('Đang hiện')->assertSee('Sản phẩm khác');
    }

    public function test_promotion_information_does_not_change_checkout_price(): void
    {
        Promotion::create($this->data(['title' => 'Thông tin không giảm tiền']));
        $variant = $this->product->variants()->first();
        $this->assertSame(9000000.0, (float) $variant->fresh()->sale_price);
    }
}
