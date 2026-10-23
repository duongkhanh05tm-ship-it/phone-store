@props(['product'])
@php
    $variant = $product->variants->sortBy(fn ($v) => $v->sale_price !== null && $v->sale_price < $v->price ? $v->sale_price : $v->price)->first();
    $price = $variant?->sale_price && $variant->sale_price < $variant->price ? $variant->sale_price : $variant?->price;
    $image = $product->images->first();
    $placeholder = asset('images/phonestore-placeholder.svg');
    $imageUrl = $image ? (filter_var($image->image_path, FILTER_VALIDATE_URL) ? $image->image_path : Storage::url($image->image_path)) : $placeholder;
@endphp


<article class="card ps-product-card h-100" aria-label="{{ $product->name }}">
    @php $sale = $variant?->sale_price && $variant->sale_price < $variant->price; @endphp
    <div class="position-relative p-3"><img src="{{ $imageUrl }}" class="card-img-top ps-product-image" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $placeholder }}';"><button type="button" class="btn btn-light position-absolute top-0 end-0 m-2 rounded-circle ps-wishlist-toggle {{ auth()->check() && auth()->user()->wishlist()->where('product_id', $product->id)->exists() ? 'text-danger' : '' }}" data-wishlist-product="{{ $product->id }}" aria-label="{{ auth()->check() && auth()->user()->wishlist()->where('product_id', $product->id)->exists() ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích' }}"><i class="bi {{ auth()->check() && auth()->user()->wishlist()->where('product_id', $product->id)->exists() ? 'bi-heart-fill' : 'bi-heart' }}"></i></button>@if ($sale)<span class="badge ps-discount-badge">-{{ $variant->price > 0 ? round((1 - ($variant->sale_price / $variant->price)) * 100) : 0 }}%</span>@endif</div>
    <div class="card-body d-flex flex-column">
        <h2 class="h6 ps-product-name">{{ $product->name }}</h2>
        <p class="small text-muted mb-2">{{ $product->brand->name }}</p>
        <div class="mt-auto"><div class="ps-product-price">{{ number_format((float) $price, 0, ',', '.') }}đ</div>@if ($sale)<del class="text-muted small">{{ number_format((float) $variant->price, 0, ',', '.') }}đ</del>@endif</div>
        <p class="small text-muted mt-2 mb-3">{{ $variant?->ram }}{{ $variant?->ram && $variant?->storage ? ' · ' : '' }}{{ $variant?->storage }}</p>
        <div class="d-flex gap-2"><a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-secondary btn-sm flex-grow-1">Xem chi tiết</a><button type="button" class="btn btn-outline-danger btn-sm" data-compare-add="{{ $product->id }}">So sánh</button></div>
    </div>
</article>
