@extends('layouts.app')
@section('title', 'So sánh điện thoại - PhoneStore')
@section('content')
<div class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><div><span class="ps-section-kicker">PHONESTORE</span><h1 class="h3 fw-bold">So sánh điện thoại</h1></div><div class="d-flex gap-2"><input id="compare-search" class="form-control" placeholder="Tìm sản phẩm..." data-suggest-url="{{ route('products.suggest') }}"><a href="{{ route('products.index') }}" class="btn btn-outline-danger">Thêm sản phẩm</a></div></div><div id="compare-search-results" class="list-group mb-3" hidden></div>
@if($products->count() < 2)<div class="alert alert-light border">Hãy chọn ít nhất 2 sản phẩm để bắt đầu so sánh.</div>@else
<div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="compare-different"><label class="form-check-label" for="compare-different">Chỉ hiển thị thông số khác nhau</label></div>
<div class="table-responsive ps-compare-table"><table class="table align-middle"><thead><tr><th class="ps-compare-label">Thông số</th>@foreach($products as $product)<th><button type="button" class="btn-close float-end" aria-label="Xóa {{ $product['name'] }}" data-compare-remove="{{ $product['id'] }}"></button><img src="{{ $product['image'] }}" class="ps-compare-image" alt="{{ $product['name'] }}"><div class="fw-bold">{{ $product['name'] }}</div><small class="text-muted">{{ $product['brand'] }}</small>@if($product['variants']->count() > 1)<select class="form-select form-select-sm mt-2" data-compare-variant="{{ $product['id'] }}"><option value="">Chọn cấu hình</option>@foreach($product['variants'] as $variant)<option value="{{ $variant['id'] }}" @selected(($product['selected']['id'] ?? null) === $variant['id'])>{{ $variant['ram'] ?: '—' }} · {{ $variant['storage'] ?: '—' }}{{ $variant['color'] ? ' · '.$variant['color'] : '' }}</option>@endforeach</select>@endif</th>@endforeach</tr></thead><tbody>
@php $specKeys = $products->flatMap(fn($p) => array_keys($p['specs']))->unique()->values(); $groups = ['Giá' => ['price','original_price','stock'], 'Cấu hình' => ['ram','storage'], 'Thông số kỹ thuật' => $specKeys]; @endphp
@foreach($groups as $group => $keys)
<tr class="table-light"><th colspan="{{ $products->count()+1 }}">{{ $group }}</th></tr>
@foreach($keys as $key)
<tr data-compare-row><th class="ps-compare-label">{{ $key }}</th>
@foreach($products as $product)
<td>
@if(in_array($key, ['price','original_price','stock','ram','storage']))
    @if($product['selected'])
        @if(in_array($key, ['price','original_price'])){{ number_format($product['selected'][$key],0,',','.').'đ' }}@else{{ $product['selected'][$key] ?: 'Chưa cập nhật' }}@endif
    @else
        {{ $key === 'price' ? 'Từ '.number_format($product['variants']->min('price'),0,',','.').'đ' : 'Chưa chọn cấu hình' }}
    @endif
@else
    {{ $product['specs'][$key] ?? 'Chưa cập nhật' }}
@endif
</td>
@endforeach
</tr>
@endforeach
@endforeach
</tbody></table></div><p class="small text-muted">Giá, RAM, ROM và tồn kho luôn lấy cùng variant đã chọn; sản phẩm nhiều variant cần chọn cấu hình.</p>
@endif</div>
@endsection