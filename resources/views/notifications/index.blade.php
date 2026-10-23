@extends('layouts.app')
@section('title', 'Thông báo - PhoneStore')
@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 fw-bold mb-0">Thông báo</h1><form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline-secondary btn-sm">Đánh dấu tất cả đã đọc</button></form></div>
    <div class="list-group shadow-sm">
        @forelse($notifications as $notification)
            <a href="{{ route('notifications.read', $notification->id) }}" class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'fw-bold bg-light' }}"><div>{{ $notification->data['message'] ?? 'Bạn có thông báo mới.' }}</div><small class="text-muted">{{ $notification->created_at->format('d/m/Y H:i') }}{{ $notification->read_at ? '' : ' · Chưa đọc' }}</small></a>
        @empty
            <div class="list-group-item text-center text-muted py-4">Bạn chưa có thông báo.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection
