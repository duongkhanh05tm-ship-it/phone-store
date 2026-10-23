<?php

namespace App\Http\Controllers;

use App\Models\{Cart, CartItem, ProductVariant};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    private function cart(): Cart
    {
        return Cart::firstOrCreate(['user_id' => auth()->id()], ['session_id' => null]);
    }

    public function index(): View
    {
        $cart = $this->cart()->load(['items.variant.product.brand', 'items.variant.product.images']);
        return view('cart.index', compact('cart'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_variant_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1'], 'buy_now' => ['sometimes', 'boolean']]);
        $variant = ProductVariant::with('product')->whereKey($data['product_variant_id'])->where('is_active', true)->first();
        abort_if(!$variant || !$variant->product->is_active, 404);
        if ($variant->stock_quantity < 1) {
            return back()->withErrors(['quantity' => 'Phiên bản này hiện đã hết hàng.']);
        }
        $item = $this->cart()->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $quantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];
        if ($quantity > $variant->stock_quantity) return back()->withErrors(['quantity' => 'Số lượng vượt quá tồn kho hiện tại.']);
        $item->quantity = $quantity; $item->save();
        return $request->boolean('buy_now')
            ? redirect()->route('checkout.index')
            : redirect()->route('cart.index')->with('status', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->cart()->where('user_id', auth()->id())->exists(), 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $variant = $cartItem->variant()->firstOrFail();
        if (!$variant->is_active || !$variant->product()->where('is_active', true)->exists() || $data['quantity'] > $variant->stock_quantity) return back()->withErrors(['quantity' => 'Số lượng không hợp lệ hoặc vượt tồn kho.']);
        $cartItem->update(['quantity' => $data['quantity']]);
        return back()->with('status', 'Đã cập nhật giỏ hàng.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->cart()->where('user_id', auth()->id())->exists(), 404);
        $cartItem->delete(); return back()->with('status', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
}
