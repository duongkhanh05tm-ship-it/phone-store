@extends('layouts.app')

@section('title', 'Đăng nhập - PhoneStore')

@section('content')
<div class="container py-5" style="max-width: 560px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-2">Đăng nhập</h1>
            <p class="text-muted mb-4">Đăng nhập để quản lý tài khoản và đơn hàng.</p>
            @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label" for="password">Mật khẩu</label><input class="form-control" id="password" name="password" type="password" required></div>
                <div class="form-check mb-4"><input class="form-check-input" id="remember" name="remember" type="checkbox" value="1"><label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label></div>
                <button class="btn ps-btn-primary w-100" type="submit">Đăng nhập</button>
            </form>
            <p class="text-center mt-4 mb-0">Chưa có tài khoản? <a class="ps-text-link" href="{{ route('register') }}">Đăng ký ngay</a></p>
        </div>
    </div>
</div>
@endsection
