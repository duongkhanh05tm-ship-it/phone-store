@extends('layouts.app')

@section('title', $product->name . ' - PhoneStore')

@section('content')
@php
    $placeholder = asset('images/phonestore-placeholder.svg');
    $firstImage = $product->images->whereNull('product_variant_id')->first();
    $firstUrl = $firstImage
        ? (filter_var($firstImage->image_path, FILTER_VALIDATE_URL) ? $firstImage->image_path : Storage::url($firstImage->image_path))
        : $placeholder;

    // Normalize grouped and legacy flat data without changing the stored specifications.
    $rawSpecifications = $product->specifications;
    if (is_array($rawSpecifications) && !array_is_list($rawSpecifications)) {
        $rawSpecifications = [['group' => 'Thông tin chung', 'rows' => collect($rawSpecifications)->map(fn ($value, $name) => [
            'name' => $name,
            'value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE),
        ])->values()->all()]];
    }
    $specifications = collect($rawSpecifications ?? [])->map(function ($group) {
        $rows = collect($group['rows'] ?? [])->filter(fn ($row) => is_array($row) && filled($row['name'] ?? null) && filled($row['value'] ?? null))
            ->map(fn ($row) => ['name' => (string) $row['name'], 'value' => is_scalar($row['value']) ? (string) $row['value'] : json_encode($row['value'], JSON_UNESCAPED_UNICODE)])
            ->values();
        return ['group' => trim((string) ($group['group'] ?? '')), 'rows' => $rows];
    })->filter(fn ($group) => $group['group'] !== '' && $group['rows']->isNotEmpty())->values();
    $previewSpecifications = collect();
    $remaining = 6;
    foreach ($specifications as $group) {
        if ($remaining <= 0) break;
        $rows = $group['rows']->take($remaining);
        $previewSpecifications->push(['group' => $group['group'], 'rows' => $rows]);
        $remaining -= $rows->count();
    }
    $hasMoreSpecifications = $specifications->flatMap(fn ($group) => $group['rows'])->count() > 6;
@endphp

<div class="container py-4 ps-product-detail">
    <nav class="ps-breadcrumb small text-muted mb-3" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a>
        <span class="mx-1">/</span>{{ $product->category->name }}<span class="mx-1">/</span>{{ $product->name }}
    </nav>

    <div class="d-flex justify-content-end mb-3"><button type="button" class="btn btn-outline-danger ps-wishlist-toggle {{ auth()->check() && auth()->user()->wishlist()->where('product_id', $product->id)->exists() ? 'text-danger' : '' }}" data-wishlist-product="{{ $product->id }}"><i class="bi {{ auth()->check() && auth()->user()->wishlist()->where('product_id', $product->id)->exists() ? 'bi-heart-fill' : 'bi-heart' }} me-1"></i> Yêu thích</button></div>
    <div class="row g-4 g-lg-5">
        <div class="col-lg-5">
            @php
                $toUrl = fn ($image) => filter_var($image->image_path, FILTER_VALIDATE_URL) ? $image->image_path : Storage::url($image->image_path);
                $galleryImages = $product->images->whereNull('product_variant_id')->map($toUrl)->values();
                $placeholderOnly = $galleryImages->isEmpty() ? collect([$placeholder]) : $galleryImages;
            @endphp
            <div class="ps-gallery" data-gallery='@json($placeholderOnly)' data-product-name="{{ $product->name }}">
                <div class="ps-gallery-main bg-white rounded p-3 position-relative">
                <button type="button" class="ps-gallery-arrow ps-gallery-prev" aria-label="Ảnh trước">‹</button><button type="button" class="ps-gallery-arrow ps-gallery-next" aria-label="Ảnh tiếp theo">›</button><span class="ps-gallery-counter" aria-live="polite">{{ $galleryImages->count() ? '1/'.$galleryImages->count() : '0/0' }}</span>
                <button type="button" class="ps-gallery-image-button" aria-label="Phóng to ảnh"><img id="product-image" class="ps-gallery-image" src="{{ $firstUrl }}" alt="{{ $product->name }}" onerror="this.src='{{ $placeholder }}'"></button>
            </div>
            @if($placeholderOnly->isNotEmpty())
                <div class="ps-gallery-thumbs" aria-label="Ảnh sản phẩm">
                    @foreach($galleryImages as $index => $imageUrl)
                        <button type="button" class="ps-gallery-thumb {{ $index === 0 ? 'active' : '' }}" data-gallery-index="{{ $index }}"><img src="{{ $imageUrl }}" alt="Ảnh {{ $index + 1 }} của {{ $product->name }}"></button>
                    @endforeach
                </div>
            @endif
            </div>
            <div class="modal fade" id="productImageModal" tabindex="-1" aria-label="Ảnh lớn sản phẩm"><div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content bg-dark"><div class="modal-header border-0"><button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body text-center"><img id="product-image-large" class="img-fluid ps-gallery-large" src="{{ $firstUrl }}" alt="{{ $product->name }}"></div></div></div></div>
        </div>

        <div class="col-lg-7">
            <p class="text-muted mb-1">{{ $product->brand->name }} · {{ $product->category->name }}</p>
            <h1 class="h2 fw-bold">{{ $product->name }}</h1>
            @if($product->description)
                <p class="text-muted" style="white-space: pre-line">{{ $product->description }}</p>
            @endif

            @php
                $variantPayload = $product->variants->map(function ($variant) use ($product, $toUrl) {
                    $effective = $variant->sale_price !== null && $variant->sale_price < $variant->price ? $variant->sale_price : $variant->price;
                    $ownImages = $variant->images->map($toUrl)->values();
                    $sameColorImages = $product->variants->where('color', $variant->color)->flatMap->images->map($toUrl)->values();
                    $commonImages = $product->images->whereNull('product_variant_id')->map($toUrl)->values();
                    $images = $ownImages->isNotEmpty() ? $ownImages : ($sameColorImages->isNotEmpty() ? $sameColorImages : $commonImages);
                    return ['id'=>$variant->id, 'ram'=>$variant->ram, 'storage'=>$variant->storage, 'color'=>$variant->color, 'price'=>(float)$variant->price, 'sale_price'=>$variant->sale_price !== null ? (float)$variant->sale_price : null, 'effective'=>(float)$effective, 'discount'=>(float)$variant->price > 0 && $variant->sale_price !== null && $variant->sale_price < $variant->price ? (int) round((1 - ($variant->sale_price / $variant->price)) * 100) : 0, 'stock'=>(int)$variant->stock_quantity, 'images'=>$images->values()];
                })->values();
                $configurations = $variantPayload->map(fn ($item) => trim(implode(' · ', array_filter([$item['ram'], $item['storage']]))) ?: 'Mặc định')->unique()->values();
                $colors = $variantPayload->pluck('color')->filter()->unique()->values();
            @endphp
            <button type="button" class="btn btn-outline-danger mb-3" data-compare-add="{{ $product->id }}">So sánh sản phẩm</button>
            <form method="POST" action="{{ route('cart.items.store') }}" id="product-purchase-form" class="ps-purchase-card bg-white rounded-4 p-3 p-md-4">
                @csrf
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h6 fw-bold mb-0">Chọn phiên bản</h2>
                    <span class="small text-muted">Giá theo phiên bản</span>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3" id="configuration-choices">
                    @foreach($configurations as $configuration)@php $configurationVariant = $variantPayload->first(fn ($item) => (trim(implode(' · ', array_filter([$item['ram'], $item['storage']]))) ?: 'Mặc định') === $configuration); $configurationHasStock = $variantPayload->contains(fn ($item) => (trim(implode(' · ', array_filter([$item['ram'], $item['storage']]))) ?: 'Mặc định') === $configuration && $item['stock'] > 0); @endphp<button type="button" class="variant-option" data-configuration="{{ $configuration }}" data-price="{{ number_format($configurationVariant['effective'], 0, ',', '.') }}đ" data-stock="{{ $configurationVariant['stock'] }}" aria-label="Cấu hình {{ $configuration }}{{ $configurationHasStock ? '' : ', hết hàng' }}">{{ $configuration }}@unless($configurationHasStock) <span class="small text-muted">(Hết hàng)</span>@endunless</button>@endforeach
                </div>
                @if($colors->isNotEmpty())<h2 class="h6 mt-3">MÀU SẮC</h2><div class="d-flex flex-wrap gap-2 mb-3" id="color-choices">@foreach($colors as $color)<button type="button" class="variant-option" data-color="{{ $color }}">{{ $color }}</button>@endforeach</div>@endif
                <input type="hidden" name="product_variant_id" id="product-variant-id" required>
                <div class="ps-price-box border rounded p-3 mb-3"><div id="variant-price" class="h3 text-danger mb-1">Chọn phiên bản và màu sắc</div><div id="variant-original-price" class="text-muted text-decoration-line-through"></div><span id="variant-discount" class="badge text-bg-danger d-none"></span><div id="variant-stock" class="mt-2"></div></div>
                <div class="input-group mb-3" style="max-width: 180px"><span class="input-group-text">Số lượng</span><input class="form-control" type="number" name="quantity" id="variant-quantity" min="1" value="1" required></div>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button class="btn btn-outline-danger btn-lg flex-fill" type="submit" id="add-to-cart" disabled>Thêm vào giỏ hàng</button>
                    <button class="btn ps-btn-primary btn-lg flex-fill" type="submit" id="buy-now" name="buy_now" value="1" disabled>Mua ngay</button>
                </div>
            </form>
            <div id="sticky-purchase-bar" class="ps-sticky-purchase" aria-label="Mua sản phẩm" hidden>
                <div class="container d-flex align-items-center justify-content-between gap-3">
                    <div class="min-w-0"><strong class="d-block text-truncate">{{ $product->name }}</strong><span id="sticky-variant-price" class="text-danger fw-bold">Chọn phiên bản</span></div>
                    <button type="button" class="btn ps-btn-primary flex-shrink-0" id="sticky-add-to-cart" disabled>Thêm vào giỏ</button>
                </div>
            </div>
            @if($product->promotions->isNotEmpty())<section class="ps-promotions border rounded p-3 mt-3" aria-labelledby="promotions-title"><h2 id="promotions-title" class="h5"><i class="bi bi-gift-fill text-danger me-2" aria-hidden="true"></i>ƯU ĐÃI ĐI KÈM</h2><ul class="mb-0 ps-3">@foreach($product->promotions as $promotion)<li class="mb-2"><strong>{{ $promotion->title }}</strong><div class="text-muted">{{ $promotion->content }}</div></li>@endforeach</ul></section>@endif
            <script type="application/json" id="variant-data">@json($variantPayload)</script>
        </div>
    </div>

    <section class="mt-5" aria-labelledby="description-title">
        <h2 id="description-title" class="h4">ĐÁNH GIÁ TỔNG QUAN / ĐẶC ĐIỂM NỔI BẬT</h2>
        @if($product->description)
            <div id="product-description" class="product-description collapse show" style="white-space: pre-line; max-height: 12rem; overflow: hidden">{{ $product->description }}</div>
            @if(mb_strlen($product->description) > 700)
                <button type="button" class="btn btn-link px-0" data-bs-toggle="collapse" data-bs-target="#product-description" aria-expanded="false">Xem thêm / Thu gọn</button>
            @endif
        @else
            <p class="text-muted">Sản phẩm chưa có nội dung giới thiệu.</p>
        @endif
    </section>

    @if($specifications->isNotEmpty())
        <section class="mt-5 ps-specifications" aria-labelledby="specifications-title">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h2 id="specifications-title" class="h4 mb-0">THÔNG SỐ KỸ THUẬT</h2>
                <span class="small text-muted">Thông tin sản phẩm</span>
            </div>
            <div class="ps-spec-table">
                @foreach($previewSpecifications as $group)
                    <div class="ps-spec-group">
                        <h3 class="ps-spec-group-title">{{ $group['group'] }}</h3>
                        @foreach($group['rows'] as $row)
                            <div class="ps-spec-row">
                                <div class="ps-spec-label">{{ $row['name'] }}</div>
                                <div class="ps-spec-value">{{ $row['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            @if($hasMoreSpecifications)
                <button type="button" class="btn btn-outline-secondary mt-3" data-bs-toggle="modal" data-bs-target="#allSpecifications">Xem toàn bộ thông số kỹ thuật</button>
            @endif
        </section>

        <div class="modal fade" id="allSpecifications" tabindex="-1" aria-labelledby="allSpecificationsLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"><h2 class="modal-title h5" id="allSpecificationsLabel">Toàn bộ thông số kỹ thuật</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                    <div class="modal-body">
                        @foreach($specifications as $group)
                            <h3 class="h6 text-uppercase mt-3">{{ $group['group'] }}</h3>
                            <div class="table-responsive"><table class="table table-sm table-striped"><tbody>@foreach($group['rows'] as $row)<tr><th class="fw-normal">{{ $row['name'] }}</th><td>{{ $row['value'] }}</td></tr>@endforeach</tbody></table></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section class="mt-5 ps-reviews" aria-labelledby="reviews-title">
        <h2 id="reviews-title" class="h4 mb-3">ĐÁNH GIÁ {{ $product->name }}</h2>
        <div class="card border-0 shadow-sm mb-3"><div class="card-body"><div class="row g-4 align-items-center">
            <div class="col-md-3 text-center"><div class="display-4 fw-bold">{{ number_format($reviewAverage, 1) }}<small class="fs-4 text-muted">/5</small></div><div class="text-warning fs-5">{{ str_repeat('★', (int) round($reviewAverage)) }}<span class="text-muted">{{ str_repeat('★', 5 - (int) round($reviewAverage)) }}</span></div><div class="text-muted">{{ $reviewTotal }} lượt đánh giá</div>
                @if(auth()->check() && $canReview && !$alreadyReviewed)<a href="#review-form" class="btn btn-danger mt-2">Viết đánh giá</a>@elseif(!auth()->check())<a href="{{ route('login') }}" class="btn btn-outline-danger mt-2">Đăng nhập để đánh giá</a>@endif
            </div>
            <div class="col-md-6">@foreach($reviewDistribution as $distribution)<div class="d-flex align-items-center gap-2 mb-2"><span class="text-nowrap">{{ $distribution['rating'] }} <span class="text-warning">★</span></span><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar bg-danger" style="width: {{ $distribution['percentage'] }}%"></div></div><small class="text-muted text-nowrap">{{ $distribution['count'] }} đánh giá</small></div>@endforeach</div>
            <div class="col-md-3 border-start"><strong>Đánh giá theo sao</strong><p class="small text-muted mb-0 mt-2">Ý kiến thực tế từ khách hàng đã mua sản phẩm.</p></div>
        </div></div></div>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <strong>Lọc đánh giá:</strong>
            <a class="btn btn-sm {{ !$reviewFilter ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ route('products.show', $product->slug) }}#reviews-title">Tất cả</a>
            @for($star = 5; $star >= 1; $star--)
                <a class="btn btn-sm {{ $reviewFilter === $star ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ request()->fullUrlWithQuery(['rating' => $star]) }}#reviews-title">{{ $star }} sao</a>
            @endfor
        </div>
        @if(session('review_status'))<div class="alert alert-success">{{ session('review_status') }}</div>@endif
        @if($errors->has('review'))<div class="alert alert-danger">{{ $errors->first('review') }}</div>@endif
        @if(auth()->check() && $canReview && !$alreadyReviewed)<form id="review-form" method="POST" action="{{ route('products.reviews.store', $product) }}" class="card card-body mb-4">@csrf<h3 class="h6">Chia sẻ trải nghiệm của bạn</h3><div class="mb-3"><label class="form-label">Số sao</label><select name="rating" class="form-select" required><option value="">Chọn số sao</option>@for($star = 5; $star >= 1; $star--)<option value="{{ $star }}" @selected(old('rating') == $star)>{{ $star }} sao</option>@endfor</select></div><div class="mb-3"><label class="form-label">Nội dung</label><textarea name="comment" class="form-control" rows="4" maxlength="2000" required>{{ old('comment') }}</textarea></div><button class="btn btn-danger">Gửi đánh giá</button></form>@endif
        <div class="card border-0 shadow-sm"><div class="card-body">@forelse($reviews as $review)<article class="border-bottom py-3"><div class="d-flex gap-3"><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:38px;height:38px">{{ mb_strtoupper(mb_substr($review->user->name, 0, 1)) }}</div><div class="flex-grow-1"><strong>{{ $review->user->name }}</strong><div><span class="text-warning">{{ str_repeat('★', $review->rating) }}</span><small class="text-muted ms-2">{{ $review->created_at->diffForHumans() }}</small></div>@if($review->verified_purchase)<span class="text-success small">✓ Đã mua hàng</span>@endif<p class="mb-0 mt-2">{{ $review->comment }}</p></div></div></article>@empty<p class="text-muted mb-0">Chưa có đánh giá nào cho sản phẩm này.</p>@endforelse</div></div>
        @if($reviews->hasPages())<div class="mt-3">{{ $reviews->fragment('reviews-title')->links() }}</div>@endif
    </section>

    @if($related->isNotEmpty())
        <section class="mt-5"><h2 class="h4">Sản phẩm liên quan</h2><div class="row g-3">@foreach($related as $item)<div class="col-6 col-lg-3">@include('components.product-card', ['product' => $item])</div>@endforeach</div></section>
    @endif
</div>

<script>
const gallery = document.querySelector('.ps-gallery');
const placeholder = @json($placeholder);
const galleryState = { images: gallery ? JSON.parse(gallery.dataset.gallery || '[]') : [], index: 0 };
const renderGallery = () => { if (!gallery) return; const images = galleryState.images; galleryState.index = images.length ? Math.min(galleryState.index, images.length - 1) : 0; const url = images[galleryState.index] || placeholder; const main = document.getElementById('product-image'); const large = document.getElementById('product-image-large'); main.src = url; if (large) large.src = url; gallery.dataset.gallery = JSON.stringify(images); gallery.querySelector('.ps-gallery-counter').textContent = images.length ? `${galleryState.index + 1}/${images.length}` : '0/0'; gallery.querySelector('.ps-gallery-prev').hidden = images.length < 2; gallery.querySelector('.ps-gallery-next').hidden = images.length < 2; const thumbs = gallery.querySelector('.ps-gallery-thumbs'); if (thumbs) thumbs.innerHTML = images.map((image, i) => `<button type="button" class="ps-gallery-thumb ${i === galleryState.index ? 'active' : ''}" data-gallery-index="${i}"><img src="${image}" alt="Ảnh ${i + 1}"></button>`).join(''); };
window.setProductGallery = images => { galleryState.images = Array.isArray(images) ? images.filter(Boolean) : []; galleryState.index = 0; renderGallery(); };
if (gallery) { gallery.addEventListener('click', e => { const thumb = e.target.closest('[data-gallery-index]'); if (thumb) { galleryState.index = Number(thumb.dataset.galleryIndex); renderGallery(); } }); gallery.querySelector('.ps-gallery-prev')?.addEventListener('click', () => { galleryState.index--; renderGallery(); }); gallery.querySelector('.ps-gallery-next')?.addEventListener('click', () => { galleryState.index++; renderGallery(); }); let start = 0; gallery.addEventListener('touchstart', e => start = e.changedTouches[0].clientX, {passive:true}); gallery.addEventListener('touchend', e => { const delta = e.changedTouches[0].clientX - start; if (Math.abs(delta) > 40) { galleryState.index += delta < 0 ? 1 : -1; renderGallery(); } }, {passive:true}); gallery.querySelector('.ps-gallery-image-button')?.addEventListener('click', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('productImageModal')).show()); renderGallery(); }

const variantData = JSON.parse(document.getElementById('variant-data')?.textContent || '[]');
const selected = { configuration: null, color: null };
const money = value => new Intl.NumberFormat('vi-VN').format(value) + 'đ';
const configurationOf = variant => [variant.ram, variant.storage].filter(Boolean).join(' · ') || 'Mặc định';
const refreshOptions = () => {
    document.querySelectorAll('[data-configuration]').forEach(button => { const available = variantData.some(v => configurationOf(v) === button.dataset.configuration && (!selected.color || v.color === selected.color)); button.disabled = !available; button.classList.toggle('disabled', !available); });
    document.querySelectorAll('[data-color]').forEach(button => { const available = variantData.some(v => v.color === button.dataset.color && (!selected.configuration || configurationOf(v) === selected.configuration)); button.disabled = !available; button.classList.toggle('disabled', !available); });
};
const applyVariant = () => {
    let variant = variantData.find(v => (!selected.configuration || configurationOf(v) === selected.configuration) && (!selected.color || v.color === selected.color));
    const availableVariants = variantData.filter(v => v.stock > 0);
    if (!selected.configuration && [...new Set(availableVariants.map(configurationOf))].length === 1) selected.configuration = configurationOf(availableVariants[0]);
    if (!selected.color && availableVariants.length === 1 && availableVariants[0].color) selected.color = availableVariants[0].color;
    variant = variantData.find(v => (!selected.configuration || configurationOf(v) === selected.configuration) && (!selected.color || v.color === selected.color));
    if (!variant && selected.configuration && selected.color) {
        const configurationExists = variantData.some(v => configurationOf(v) === selected.configuration && v.color === selected.color);
        if (!configurationExists) {
            selected.color = null;
            variant = variantData.find(v => configurationOf(v) === selected.configuration);
        }
    }
    const price = document.getElementById('variant-price'), original = document.getElementById('variant-original-price'), discount = document.getElementById('variant-discount'), stock = document.getElementById('variant-stock'), id = document.getElementById('product-variant-id'), quantity = document.getElementById('variant-quantity'), add = document.getElementById('add-to-cart'), buyNow = document.getElementById('buy-now');
    if (!variant || !selected.configuration || (variant.color && !selected.color)) { id.value = ''; price.textContent = 'Chọn phiên bản và màu sắc'; original.textContent = ''; discount.textContent = ''; discount.classList.add('d-none'); stock.textContent = ''; add.disabled = true; if (buyNow) buyNow.disabled = true; const stickyPrice = document.getElementById('sticky-variant-price'), stickyAdd = document.getElementById('sticky-add-to-cart'); if (stickyPrice) stickyPrice.textContent = 'Chọn phiên bản'; if (stickyAdd) stickyAdd.disabled = true; return; }
    id.value = variant.id; price.textContent = money(variant.effective); original.textContent = variant.sale_price !== null && variant.sale_price < variant.price ? money(variant.price) : ''; discount.textContent = variant.discount > 0 ? `Giảm ${variant.discount}%` : ''; discount.classList.toggle('d-none', variant.discount < 1); stock.textContent = variant.stock > 0 ? `Còn ${variant.stock} sản phẩm` : 'Hết hàng'; quantity.max = variant.stock; quantity.value = Math.min(Math.max(1, Number(quantity.value) || 1), variant.stock || 1); add.disabled = variant.stock < 1; if (buyNow) buyNow.disabled = variant.stock < 1;
    const stickyPrice = document.getElementById('sticky-variant-price'), stickyAdd = document.getElementById('sticky-add-to-cart');
    if (stickyPrice) stickyPrice.textContent = variant ? money(variant.effective) : 'Chọn phiên bản';
    if (stickyAdd) stickyAdd.disabled = !variant || variant.stock < 1;
    if (variant.images?.length && window.setProductGallery) window.setProductGallery(variant.images);
};
document.querySelectorAll('.variant-option').forEach(button => button.addEventListener('click', () => { if (button.disabled) return; if (button.dataset.configuration !== undefined) selected.configuration = button.dataset.configuration; if (button.dataset.color !== undefined) selected.color = button.dataset.color; document.querySelectorAll('.variant-option').forEach(item => item.classList.toggle('active', (item.dataset.configuration && item.dataset.configuration === selected.configuration) || (item.dataset.color && item.dataset.color === selected.color))); refreshOptions(); applyVariant(); }));
refreshOptions();
const stickyBar = document.getElementById('sticky-purchase-bar');
const purchaseForm = document.getElementById('product-purchase-form');
const stickyButton = document.getElementById('sticky-add-to-cart');
if (stickyButton && purchaseForm) stickyButton.addEventListener('click', () => { if (!stickyButton.disabled) purchaseForm.requestSubmit(); });
if (stickyBar && purchaseForm && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(([entry]) => { stickyBar.hidden = entry.isIntersecting; }, { threshold: 0.05 });
    observer.observe(purchaseForm);
}
window.setProductGallery = function (images) { const gallery = document.querySelector('.ps-gallery'); if (!gallery || !images.length) return; gallery.dataset.gallery = JSON.stringify(images); const main = document.getElementById('product-image'); const large = document.getElementById('product-image-large'); main.src = images[0]; if (large) large.src = images[0]; gallery.querySelector('.ps-gallery-counter').textContent = `1/${images.length}`; gallery.querySelectorAll('.ps-gallery-thumb').forEach((thumb, index) => { thumb.querySelector('img').src = images[index] || images[0]; thumb.classList.toggle('active', index === 0); }); };
</script>
@endsection
