<nav class="navbar navbar-expand-lg ps-navbar" aria-label="Điều hướng chính">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ps-main-nav" aria-controls="ps-main-nav" aria-expanded="false" aria-label="Mở menu"><i class="bi bi-list"></i></button>
        <div class="collapse navbar-collapse" id="ps-main-nav">
            <ul class="navbar-nav gap-lg-2">
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="{{ url('/') }}"><i class="bi bi-house-door me-1"></i>Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index') }}">Điện thoại</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('products.index') }}">Thương hiệu</a></li>
                @foreach(($navBrands ?? collect()) as $brand)
                    <li class="nav-item"><a class="nav-link" href="{{ route('products.index', ['brand_id' => $brand->id]) }}">{{ $brand->name }}</a></li>
                @endforeach
                <li class="nav-item"><span class="nav-link ps-nav-disabled">Phụ kiện <small>(sắp có)</small></span></li>
            </ul>
        </div>
    </div>
</nav>
