<?php
namespace Tests\Feature;

use App\Models\{Brand, Category, Product, ProductVariant, User, Cart, CartItem, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartOrderTest extends TestCase
{
    use RefreshDatabase;
    private function product(int $stock = 5): ProductVariant
    {
        $brand=Brand::create(['name'=>'Brand','slug'=>'brand','is_active'=>true]);
        $cat=Category::create(['name'=>'Cat','slug'=>'cat','is_active'=>true]);
        $product=Product::create(['brand_id'=>$brand->id,'category_id'=>$cat->id,'name'=>'Phone','slug'=>'phone','is_active'=>true]);
        return ProductVariant::create(['product_id'=>$product->id,'sku'=>'SKU-1','price'=>100,'sale_price'=>90,'stock_quantity'=>$stock,'is_active'=>true]);
    }
    public function test_guest_is_redirected_and_user_can_add_and_merge_cart_item(): void
    {
        $variant=$this->product();
        $this->post(route('cart.items.store'),['product_variant_id'=>$variant->id,'quantity'=>1])->assertRedirect(route('login'));
        $user=User::factory()->create(); $this->actingAs($user);
        $this->post(route('cart.items.store'),['product_variant_id'=>$variant->id,'quantity'=>2])->assertRedirect(route('cart.index'));
        $this->post(route('cart.items.store'),['product_variant_id'=>$variant->id,'quantity'=>2]);
        $this->assertDatabaseHas('cart_items',['product_variant_id'=>$variant->id,'quantity'=>4]);
        $this->post(route('cart.items.store'),['product_variant_id'=>$variant->id,'quantity'=>2])->assertSessionHasErrors('quantity');
    }
    public function test_add_to_cart_rejects_unknown_and_inactive_variants(): void
    {
        $variant = $this->product();
        $inactive = ProductVariant::create(['product_id' => $variant->product_id, 'sku' => 'INACTIVE-1', 'price' => 100, 'stock_quantity' => 5, 'is_active' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), ['product_variant_id' => 999999, 'quantity' => 1])->assertNotFound();
        $this->actingAs($user)->post(route('cart.items.store'), ['product_variant_id' => $inactive->id, 'quantity' => 1])->assertNotFound();
    }

    public function test_add_to_cart_rejects_zero_stock_and_never_accepts_client_price(): void
    {
        $variant = $this->product(0);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1, 'price' => 0])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_uses_database_price_reduces_stock_and_clears_cart(): void
    {
        $variant=$this->product(3); $user=User::factory()->create(); $cart=Cart::create(['user_id'=>$user->id]); CartItem::create(['cart_id'=>$cart->id,'product_variant_id'=>$variant->id,'quantity'=>2]);
        $response=$this->actingAs($user)->post(route('checkout.store'),['customer_name'=>'Nguyen Van A','customer_phone'=>'0900000000','shipping_address'=>'Ha Noi','payment_method'=>'cod','checkout_token'=>'token-1']);
        $order=Order::first(); $response->assertRedirect(route('orders.show',$order));
        $this->assertSame('180.00',$order->total_amount); $this->assertDatabaseHas('order_items',['order_id'=>$order->id,'unit_price'=>90,'quantity'=>2,'line_total'=>180]); $this->assertDatabaseHas('product_variants',['id'=>$variant->id,'stock_quantity'=>1]); $this->assertDatabaseCount('cart_items',0);
        $this->actingAs($user)->post(route('checkout.store'),['customer_name'=>'x','customer_phone'=>'x','shipping_address'=>'x','payment_method'=>'cod','checkout_token'=>'token-1'])->assertRedirect(route('orders.show',$order));
        $this->assertDatabaseCount('orders',1);
    }
    public function test_checkout_rejects_insufficient_stock_and_orders_are_private(): void
    {
        $variant=$this->product(1); $user=User::factory()->create(); $cart=Cart::create(['user_id'=>$user->id]); CartItem::create(['cart_id'=>$cart->id,'product_variant_id'=>$variant->id,'quantity'=>2]);
        $this->actingAs($user)->post(route('checkout.store'),['customer_name'=>'x','customer_phone'=>'x','shipping_address'=>'x','payment_method'=>'cod','checkout_token'=>'bad'])->assertSessionHasErrors('cart');
        $order=Order::create(['user_id'=>User::factory()->create()->id,'customer_name'=>'x','customer_phone'=>'x','shipping_address'=>'x','subtotal'=>1,'total_amount'=>1,'payment_method'=>'cod','payment_status'=>'pending','order_status'=>'pending']);
        $this->actingAs($user)->get(route('orders.show',$order))->assertNotFound();
    }
}
