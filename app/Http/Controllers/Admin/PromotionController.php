<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.promotions.index', ['promotions' => Promotion::with('product')->orderBy('sort_order')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', ['promotion' => new Promotion(['is_active' => true]), 'products' => Product::orderBy('name')->get(), 'heading' => 'Thêm ưu đãi']);
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        Promotion::create($data);
        return redirect()->route('admin.promotions.index')->with('status', 'Đã tạo ưu đãi.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.form', ['promotion' => $promotion, 'products' => Product::orderBy('name')->get(), 'heading' => 'Sửa ưu đãi']);
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $promotion->update($data);
        return redirect()->route('admin.promotions.index')->with('status', 'Đã cập nhật ưu đãi.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();
        return back()->with('status', 'Đã xóa ưu đãi.');
    }

    public function status(Promotion $promotion): RedirectResponse
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);
        return back()->with('status', $promotion->is_active ? 'Đã bật ưu đãi.' : 'Đã tắt ưu đãi.');
    }
}
