<?php
namespace Tests\Feature\Admin;

use App\Models\{Brand, Category, Order, OrderItem, Product, ProductVariant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;
    private User $admin;
    protected function setUp(): void { parent::setUp(); $this->admin=User::factory()->create(['role'=>'admin']); }
    private function order(array $data=[]): Order { return Order::create(array_merge(['user_id'=>User::factory()->create()->id,'customer_name'=>'Nguyen Van A','customer_phone'=>'0900000000','shipping_address'=>'Ha Noi','subtotal'=>200,'total_amount'=>200,'payment_method'=>'cod','payment_status'=>'pending','order_status'=>'pending'],$data)); }
    public function test_access_and_admin_can_search_and_view(): void
    {
        $order=$this->order(); Order::create(['user_id'=>$order->user_id,'customer_name'=>'Other','customer_phone'=>'0911111111','shipping_address'=>'HCM','subtotal'=>100,'total_amount'=>100,'payment_method'=>'cod','payment_status'=>'pending','order_status'=>'completed']);
        $this->get(route('admin.orders.index'))->assertRedirect(route('login')); $this->actingAs(User::factory()->create())->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.orders.index',['q'=>$order->id,'status'=>'pending']))->assertOk()->assertSee($order->customer_name);
        $this->actingAs($this->admin)->get(route('admin.orders.show',$order))->assertOk()->assertSee('Đơn hàng #'.$order->id);
    }
    public function test_valid_status_flow_rejects_invalid_and_user_sees_new_status(): void
    {
        $order=$this->order(); $admin=$this->actingAs($this->admin);
        $admin->patch(route('admin.orders.status',$order),['status'=>'shipping'])->assertSessionHasErrors('status');
        $admin->patch(route('admin.orders.status',$order),['status'=>'confirmed'])->assertRedirect(); $admin->patch(route('admin.orders.status',$order),['status'=>'shipping'])->assertRedirect(); $admin->patch(route('admin.orders.status',$order),['status'=>'completed'])->assertRedirect();
        $this->assertDatabaseHas('orders',['id'=>$order->id,'order_status'=>'completed']); $this->actingAs(User::find($order->user_id))->get(route('orders.show',$order))->assertSee('completed');
        $admin=$this->actingAs($this->admin);
        $admin->patch(route('admin.orders.status',$order),['status'=>'cancelled'])->assertRedirect();
        $this->assertDatabaseHas('orders',['id'=>$order->id,'order_status'=>'completed']);
    }
    public function test_cancel_restores_stock_once_keeps_items_and_payment_rules(): void
    {
        $brand=Brand::create(['name'=>'Brand','slug'=>'brand','is_active'=>true]); $category=Category::create(['name'=>'Cat','slug'=>'cat','is_active'=>true]); $product=Product::create(['brand_id'=>$brand->id,'category_id'=>$category->id,'name'=>'Phone','slug'=>'phone','is_active'=>true]);
        $variant=ProductVariant::create(['product_id'=>$product->id,'sku'=>'SKU-RESTORE','price'=>100,'stock_quantity'=>3,'is_active'=>true]);
        $order=$this->order(); $item=OrderItem::create(['order_id'=>$order->id,'product_variant_id'=>$variant->id,'product_name'=>'Phone','sku'=>$variant->sku,'variant_description'=>'8GB','unit_price'=>100,'quantity'=>2,'line_total'=>200]);
        $admin=$this->actingAs($this->admin); $admin->patch(route('admin.orders.status',$order),['status'=>'cancelled'])->assertRedirect(); $this->assertDatabaseHas('product_variants',['id'=>$variant->id,'stock_quantity'=>5]); $this->assertDatabaseHas('order_items',['id'=>$item->id]);
        $admin->patch(route('admin.orders.status',$order),['status'=>'cancelled'])->assertSessionHasErrors('status'); $this->assertDatabaseHas('product_variants',['id'=>$variant->id,'stock_quantity'=>5]);
        $admin->patch(route('admin.orders.payment-status',$order),['payment_status'=>'paid'])->assertSessionHasErrors('payment_status');
    }
    public function test_admin_can_confirm_cod_payment_only_forward(): void
    {
        $order=$this->order(); $admin=$this->actingAs($this->admin); $admin->patch(route('admin.orders.payment-status',$order),['payment_status'=>'paid'])->assertRedirect(); $this->assertDatabaseHas('orders',['id'=>$order->id,'payment_status'=>'paid']); $admin->patch(route('admin.orders.payment-status',$order),['payment_status'=>'pending'])->assertSessionHasErrors('payment_status');
    }
}
