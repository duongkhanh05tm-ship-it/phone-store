@extends('layouts.app')

@section('title', 'Đăng ký - PhoneStore')

@section('content')
<div class="container py-5" style="max-width: 560px;">
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-2">Tạo tài khoản</h1><p class="text-muted mb-4">Tham gia PhoneStore để theo dõi thông tin mua hàng.</p>
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label" for="name">Họ tên</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus></div>
            <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required></div>
            <div class="mb-3"><label class="form-label" for="password">Mật khẩu</label><input class="form-control" id="password" name="password" type="password" required></div>
            <div class="mb-4"><label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required></div>
            <button class="btn ps-btn-primary w-100" type="submit">Đăng ký</button>
        </form>
        <p class="text-center mt-4 mb-0">Đã có tài khoản? <a class="ps-text-link" href="{{ route('login') }}">Đăng nhập</a></p>
    </div></div>
</div>
@endsection
