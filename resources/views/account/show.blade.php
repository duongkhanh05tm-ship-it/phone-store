@extends('layouts.app')

@section('title', 'Tài khoản - PhoneStore')

@section('content')
<div class="container py-5"><div class="row g-4"><div class="col-lg-8"><div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h1 class="h3 mb-4">Thông tin tài khoản</h1>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="d-flex align-items-center gap-3 mb-4">
            @if($user->avatar)<img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="rounded-circle" width="88" height="88" style="object-fit:cover">@else<div class="rounded-circle bg-danger text-white d-grid place-items-center" style="width:88px;height:88px;font-size:2rem">{{ strtoupper(substr($user->name, 0, 1)) }}</div>@endif
            <div><label class="form-label" for="avatar">Ảnh đại diện</label><input class="form-control" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG hoặc WEBP, tối đa 2MB.</div></div>
        </div>
        <div class="mb-3"><label class="form-label" for="name">Họ tên</label><input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div class="mb-3"><label class="form-label" for="phone">Số điện thoại</label><input class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel"></div>
        <div class="mb-3"><label class="form-label" for="address">Địa chỉ nhận hàng</label><textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $user->address) }}</textarea></div>
        <div class="mb-4"><label class="form-label" for="email">Email</label><input class="form-control" id="email" value="{{ $user->email }}" disabled><div class="form-text">Email chưa được thay đổi trong phiên bản này.</div></div><button class="btn ps-btn-primary" type="submit">Lưu thay đổi</button></form>
</div></div></div><div class="col-lg-4"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="h5">Theo dõi đơn hàng</h2><p class="text-muted">Kiểm tra trạng thái các đơn hàng bạn đã gửi.</p><a class="btn btn-outline-danger mb-3" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-1"></i>Xem đơn hàng của tôi</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-danger" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</button></form></div></div></div></div></div>
@endsection
