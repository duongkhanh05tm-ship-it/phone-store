<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao.',
            'rating.between' => 'Số sao phải từ 1 đến 5.',
            'comment.required' => 'Vui lòng nhập nội dung đánh giá.',
            'comment.min' => 'Nội dung đánh giá quá ngắn.',
            'comment.max' => 'Nội dung đánh giá không được vượt quá 2000 ký tự.',
        ]);

        $hasPurchased = $product->variants()->whereHas('orderItems', fn ($query) => $query->whereHas('order', fn ($order) => $order->where('user_id', $request->user()->id)->where('order_status', 'completed')))->exists();
        if (! $hasPurchased) {
            return back()->withErrors(['review' => 'Bạn chỉ có thể đánh giá sản phẩm sau khi hoàn tất mua hàng.']);
        }

        if (ProductReview::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists()) {
            return back()->withErrors(['review' => 'Bạn đã đánh giá sản phẩm này rồi.']);
        }

        ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'verified_purchase' => true,
        ]);

        return back()->with('review_status', 'Cảm ơn bạn đã gửi đánh giá sản phẩm.');
    }
}
