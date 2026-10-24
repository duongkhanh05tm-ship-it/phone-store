@extends('layouts.app')
@section('title', 'Đơn hàng của tôi - PhoneStore')
@section('content')
@php
    $statusMeta = [
        'pending' => ['label'=>'Chờ xác nhận', 'icon'=>'bi-hourglass-split', 'class'=>'is-pending'],
        'confirmed' => ['label'=>'Đã xác nhận', 'icon'=>'bi-patch-check-fill', 'class'=>'is-confirmed'],
        'shipping' => ['label'=>'Đang giao hàng', 'icon'=>'bi-truck', 'class'=>'is-shipping'],
        'completed' => ['label'=>'Giao thành công', 'icon'=>'bi-house-check-fill', 'class'=>'is-completed'],
        'cancelled' => ['label'=>'Đã hủy', 'icon'=>'bi-x-circle-fill', 'class'=>'is-cancelled'],
    ];
@endphp
<div class="container py-4 py-lg-5">
    <div class="ps-orders-hero mb-4"><div><span class="ps-eyebrow">PHONESTORE ACCOUNT</span><h1 class="h3 fw-bold mt-3 mb-2">Đơn hàng của tôi</h1><p class="mb-0">Theo dõi hành trình và quản lý tất cả đơn mua của bạn.</p></div><div class="ps-orders-hero-icon"><i class="bi bi-box-seam"></i></div></div>
    <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="h5 fw-bold mb-0">Lịch sử mua hàng</h2><span class="text-muted small">{{ $orders->total() }} đơn hàng</span></div>
    <div class="d-flex flex-column gap-3">
        @forelse($orders as $order)
            @php $meta = $statusMeta[$order->order_status] ?? ['label'=>$order->order_status, 'icon'=>'bi-receipt', 'class'=>'']; @endphp
            <article class="ps-order-card">
                <div class="ps-order-card-head"><div><span class="small text-muted">Mã đơn hàng</span><strong class="d-block mt-1">#{{ $order->id }}</strong></div><div class="text-end"><span class="small text-muted d-block">{{ $order->created_at->format('d/m/Y · H:i') }}</span><span class="ps-order-badge {{ $meta['class'] }} mt-2"><i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}</span></div></div>
                <div class="ps-order-card-body">@forelse($order->items->take(2) as $item)<div class="ps-order-summary-item"><div class="ps-order-product-icon"><i class="bi bi-phone"></i></div><div class="flex-grow-1 min-width-0"><strong class="d-block text-truncate">{{ $item->product_name }}</strong><span class="small text-muted">{{ $item->variant_description ?: 'Phiên bản tiêu chuẩn' }} · SL: {{ $item->quantity }}</span></div><strong>{{ number_format($item->line_total,0,',','.') }}đ</strong></div>@empty<div class="small text-muted">Thông tin sản phẩm đang được cập nhật.</div>@endforelse @if($order->items->count() > 2)<div class="small text-muted mt-2">+ {{ $order->items->count() - 2 }} sản phẩm khác</div>@endif</div>
                <div class="ps-order-card-foot"><div><span class="small text-muted d-block">Tổng thanh toán</span><strong class="text-danger fs-5">{{ number_format($order->total_amount,0,',','.') }}đ</strong></div><div class="d-flex gap-2 flex-wrap justify-content-end"><a class="btn ps-btn-primary px-3" href="{{ route('orders.show',$order) }}"><i class="bi bi-arrow-right me-1"></i>Theo dõi đơn</a>@if(in_array($order->order_status, ['pending', 'confirmed'], true))<form method="POST" action="{{ route('orders.cancel',$order) }}" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">@csrf @method('PATCH')<button class="btn btn-outline-danger px-3" type="submit"><i class="bi bi-x-circle me-1"></i>Hủy đơn</button></form>@endif</div></div>
            </article>
        @empty
            <div class="ps-empty-orders text-center"><div class="ps-empty-orders-icon"><i class="bi bi-bag-x"></i></div><h2 class="h5 fw-bold mt-3">Bạn chưa có đơn hàng nào</h2><p class="text-muted mb-4">Hãy khám phá những mẫu điện thoại mới nhất tại PhoneStore.</p><a href="{{ route('products.index') }}" class="btn ps-btn-primary"><i class="bi bi-phone me-1"></i>Khám phá sản phẩm</a></div>
        @endforelse
    </div>
    @if($orders->hasPages())<div class="mt-4">{{ $orders->links() }}</div>@endif
</div>
@endsection
