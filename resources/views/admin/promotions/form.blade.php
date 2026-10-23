@extends('layouts.admin')
@section('title',$heading.' - Admin')
@section('admin_content')
<h2 class="mb-3">{{ $heading }}</h2>
<form method="POST" action="{{ $promotion->exists ? route('admin.promotions.update',$promotion) : route('admin.promotions.store') }}" class="card card-body shadow-sm">@csrf @if($promotion->exists) @method('PUT') @endif
<div class="mb-3"><label class="form-label">Sản phẩm</label><select name="product_id" class="form-select" required><option value="">Chọn sản phẩm</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id',$promotion->product_id)==$product->id)>{{ $product->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Tiêu đề</label><input name="title" class="form-control" value="{{ old('title',$promotion->title) }}" required></div>
<div class="mb-3"><label class="form-label">Nội dung</label><textarea name="content" class="form-control" rows="4" required>{{ old('content',$promotion->content) }}</textarea></div>
<div class="row g-3"><div class="col-md-4"><label class="form-label">Bắt đầu</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at',$promotion->starts_at?->format('Y-m-d\\TH:i')) }}"></div><div class="col-md-4"><label class="form-label">Kết thúc</label><input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at',$promotion->ends_at?->format('Y-m-d\\TH:i')) }}"></div><div class="col-md-2"><label class="form-label">Thứ tự</label><input type="number" name="sort_order" min="0" class="form-control" value="{{ old('sort_order',$promotion->sort_order ?? 0) }}"></div><div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active',$promotion->exists ? $promotion->is_active : true))><label class="form-check-label">Đang bật</label></div></div></div>
<button class="btn btn-danger mt-4">Lưu ưu đãi</button></form>
@endsection
