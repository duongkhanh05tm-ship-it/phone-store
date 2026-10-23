@extends('layouts.app')

@section('title', 'Không tìm thấy trang - PhoneStore')

@section('content')
<div class="container py-5 text-center">
    <div class="py-5">
        <p class="display-1 fw-bold text-danger mb-0">404</p>
        <h1 class="h3">Không tìm thấy trang</h1>
        <p class="text-muted">Liên kết bạn truy cập không tồn tại hoặc sản phẩm đã ngừng kinh doanh.</p>
        <a class="btn btn-danger" href="{{ route('home') }}">Về trang chủ</a>
    </div>
</div>
@endsection
