@extends('layouts.app')
@section('title', 'Danh sách yêu thích - PhoneStore')
@section('content')
<div class="container py-5"><h1 class="h3 fw-bold mb-4"><i class="bi bi-heart-fill text-danger me-2"></i>Danh sách yêu thích</h1>
@if($products->isEmpty())<div class="text-center border rounded-4 py-5 px-3"><i class="bi bi-heart display-4 text-danger"></i><h2 class="h5 mt-3">Danh sách yêu thích đang trống</h2><p class="text-muted">Lưu những chiếc điện thoại bạn quan tâm để xem lại sau.</p><a href="{{ route('products.index') }}" class="btn ps-btn-primary">Khám phá sản phẩm</a></div>
@else<div class="row g-3 g-md-4">@foreach($products as $product)<div class="col-6 col-lg-3"><x-product-card :product="$product" /></div>@endforeach</div>@endif</div>
@endsection
