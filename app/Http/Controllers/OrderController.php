<?php

namespace App\Http\Controllers;

use App\Models\{Cart, Order, ProductVariant};
use App\Notifications\OrderNotification;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private function cart(): Cart
    {
        return Cart::firstOrCreate(['user_id' => auth()->id()], ['session_id' => null]);
    }

    public function checkout(): View|RedirectResponse
    {
        $cart = $this->cart()->load('items.variant.product');
        if ($cart->items->isEmpty()) return redirect()->route('cart.index')->withErrors(['cart' => 'Giỏ hàng đang trống.']);
        return view('checkout.index', compact('cart'));
    }

    public function place(Request $request): RedirectResponse
    {
        $data = $request->validate(['customer_name' => ['required', 'string', 'max:255'], 'customer_phone' => ['required', 'string', 'max:30'], 'shipping_address' => ['required', 'string', 'max:1000'], 'note' => ['nullable', 'string', 'max:2000'], 'payment_method' => ['required', 'in:cod'], 'checkout_token' => ['required', 'string', 'max:100']]);
        $tokenKey = 'checkout_token_'.$data['checkout_token'];
        if (session()->has($tokenKey)) return redirect()->route('orders.show', session($tokenKey));
        $order = DB::transaction(function () use ($data) {
            $cart = $this->cart()->load('items');
            if ($cart->items->isEmpty()) throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
            $total = 0; $lines = [];
            foreach ($cart->items as $item) {
                $variant = ProductVariant::with('product')->lockForUpdate()->find($item->product_variant_id);
                if (!$variant || !$variant->is_active || !$variant->product->is_active || $item->quantity < 1 || $item->quantity > $variant->stock_quantity) throw ValidationException::withMessages(['cart' => 'Sản phẩm không còn đủ tồn kho hoặc đã ngừng kinh doanh.']);
                $unit = ($variant->sale_price !== null && $variant->sale_price < $variant->price) ? $variant->sale_price : $variant->price;
                $line = $unit * $item->quantity; $total += $line;
                $lines[] = compact('item', 'variant', 'unit', 'line');
            }
            $order = Order::create(['user_id' => auth()->id(), 'customer_name' => $data['customer_name'], 'customer_phone' => $data['customer_phone'], 'shipping_address' => $data['shipping_address'], 'subtotal' => $total, 'shipping_fee' => 0, 'discount_amount' => 0, 'total_amount' => $total, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'pending', 'note' => $data['note'] ?? null]);
            foreach ($lines as $line) {
                $variant = $line['variant']; $item = $line['item'];
                $order->items()->create(['product_variant_id' => $variant->id, 'product_name' => $variant->product->name, 'sku' => $variant->sku, 'variant_description' => trim(implode(' · ', array_filter([$variant->ram, $variant->storage, $variant->color]))), 'unit_price' => $line['unit'], 'quantity' => $item->quantity, 'line_total' => $line['line']]);
                $variant->decrement('stock_quantity', $item->quantity);
            }
            $cart->items()->delete(); return $order;
        });
        session([$tokenKey => $order->id]);
        auth()->user()->notify(new OrderNotification($order, 'Đặt hàng #'.$order->id.' thành công và đang chờ xử lý.'));
        return redirect()->route('orders.show', $order)->with('status', 'Đặt hàng COD thành công.');
    }

    public function index(): View
    { return view('orders.index', ['orders' => auth()->user()->orders()->latest()->paginate(10)]); }

    public function show(Order $order): View
    { abort_unless($order->user_id === auth()->id(), 404); return view('orders.show', compact('order')); }
}
