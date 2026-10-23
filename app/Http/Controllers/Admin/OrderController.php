<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Order, ProductVariant};
use App\Notifications\OrderNotification;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->string('q')->toString());
                $query->where(function ($sub) use ($q) { $sub->where('id', is_numeric($q) ? $q : -1)->orWhere('customer_name', 'like', "%{$q}%")->orWhere('customer_phone', 'like', "%{$q}%"); });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('order_status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items']);
        return view('admin.orders.show', compact('order'));
    }

    public function status(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:confirmed,shipping,completed,cancelled']]);
        $current = $order->order_status;
        $next = $data['status'];
        $allowed = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['shipping', 'cancelled'], 'shipping' => ['completed', 'cancelled'], 'completed' => [], 'cancelled' => []];
        if (!in_array($next, $allowed[$current] ?? [], true)) return back()->withErrors(['status' => 'Không thể chuyển trạng thái đơn hàng theo quy trình hiện tại.']);

        if ($next === 'cancelled') {
            try {
                DB::transaction(function () use ($order) {
                    $locked = Order::with('items')->lockForUpdate()->findOrFail($order->id);
                    if ($locked->order_status === 'cancelled' || $locked->order_status === 'completed') throw ValidationException::withMessages(['status' => 'Đơn hàng không thể hủy ở trạng thái hiện tại.']);
                    foreach ($locked->items as $item) {
                        $variant = ProductVariant::lockForUpdate()->find($item->product_variant_id);
                        if (!$variant) throw ValidationException::withMessages(['status' => 'Không thể hoàn tồn kho vì một biến thể trong đơn không còn tồn tại.']);
                        $variant->increment('stock_quantity', $item->quantity);
                    }
                    $locked->update(['order_status' => 'cancelled']);
                });
            } catch (ValidationException $exception) { throw $exception; }
        } else {
            $order->update(['order_status' => $next]);
        }
        if ($order->user) {
            $statusLabels = ['confirmed' => 'đã được xác nhận', 'shipping' => 'đang được giao', 'completed' => 'đã hoàn thành', 'cancelled' => 'đã bị hủy'];
            $order->user->notify(new OrderNotification($order, 'Đơn hàng #'.$order->id.' '.$statusLabels[$next].'.'));
        }
        return back()->with('status', 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function paymentStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['payment_status' => ['required', 'in:pending,paid']]);
        if ($order->order_status === 'cancelled') return back()->withErrors(['payment_status' => 'Không thể xác nhận thanh toán cho đơn đã hủy.']);
        if ($data['payment_status'] === 'pending' && $order->payment_status === 'paid') return back()->withErrors(['payment_status' => 'Không thể chuyển trạng thái thanh toán đã xác nhận về chưa thanh toán.']);
        $order->update(['payment_status' => $data['payment_status']]);
        return back()->with('status', 'Đã cập nhật trạng thái thanh toán COD.');
    }
}
