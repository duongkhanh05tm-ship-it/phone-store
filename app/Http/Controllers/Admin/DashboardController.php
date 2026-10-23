<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalOrders' => Order::count(),
            'pendingOrders' => Order::where('order_status', 'pending')->count(),
            'shippingOrders' => Order::where('order_status', 'shipping')->count(),
            'completedOrders' => Order::where('order_status', 'completed')->count(),
            'completedRevenue' => Order::where('order_status', 'completed')->sum('total_amount'),
            'paidCodRevenue' => Order::where('payment_method', 'cod')->where('payment_status', 'paid')->sum('total_amount'),
        ]);
    }
}
