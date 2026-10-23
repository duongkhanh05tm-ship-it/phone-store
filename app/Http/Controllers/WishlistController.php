<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $products = $request->user()->wishlist()
            ->with(['product.brand', 'product.images', 'product.variants' => fn ($q) => $q->where('is_active', true)->orderBy('price')])
            ->latest()
            ->get()
            ->pluck('product')
            ->filter(fn (?Product $product) => $product?->is_active)
            ->values();
        return view('wishlist.index', compact('products'));
    }
    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);
        $product = Product::whereKey($data['product_id'])->where('is_active', true)->first();
        if (! $product) return response()->json(['message' => 'Sản phẩm không còn khả dụng.'], 422);
        $wishlist = $request->user()->wishlist()->where('product_id', $product->id)->first();
        if ($wishlist) { $wishlist->delete(); $added = false; $message = 'Đã xóa khỏi danh sách yêu thích.'; }
        else { $request->user()->wishlist()->create(['product_id' => $product->id]); $added = true; $message = 'Đã thêm vào danh sách yêu thích.'; }
        return response()->json(['added' => $added, 'count' => $request->user()->wishlist()->count(), 'message' => $message]);
    }
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $deleted = (bool) $request->user()->wishlist()->where('product_id', $product->id)->delete();
        return response()->json(['deleted' => $deleted, 'count' => $request->user()->wishlist()->count(), 'message' => 'Đã xóa khỏi danh sách yêu thích.']);
    }
}
