<header class="ps-header sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center gap-3">
            <a class="ps-brand text-decoration-none" href="{{ url('/') }}" aria-label="PhoneStore - Trang chủ">
                <span class="ps-brand-mark">P</span><span>PhoneStore</span>
            </a>
<form class="ps-search ps-live-search flex-grow-1 d-none d-md-flex" action="{{ route('products.index') }}" method="GET" role="search" data-suggest-url="{{ route('products.suggest') }}">
                <div class="position-relative flex-grow-1"><input class="form-control" type="search" name="q" placeholder="Bạn cần tìm gì?" aria-label="Tìm kiếm sản phẩm" autocomplete="off" aria-controls="ps-search-results" aria-expanded="false"><div id="ps-search-results" class="ps-search-results" role="listbox" aria-label="Gợi ý sản phẩm" hidden></div></div>
                <button class="btn ps-btn-primary" type="submit"><i class="bi bi-search"></i><span class="visually-hidden">Tìm kiếm</span></button>
            </form>
            <div class="d-flex align-items-center gap-2 ms-auto">
                @auth
                    <a class="ps-header-action d-flex align-items-center gap-1" href="{{ route('account') }}">@if(auth()->user()->avatar)<img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar" class="rounded-circle" width="30" height="30" style="object-fit:cover">@else<span class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;font-size:.85rem">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>@endif<span class="d-none d-lg-inline">{{ auth()->user()->name }}</span></a>
                    <a class="ps-header-action position-relative" href="{{ route('notifications.index') }}" aria-label="Thông báo"><i class="bi bi-bell"></i><span class="d-none d-lg-inline">Thông báo</span>@php $unreadNotifications = \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_type', get_class(auth()->user()))->where('notifiable_id', auth()->id())->whereNull('read_at')->count(); @endphp @if($unreadNotifications)<span class="badge rounded-pill bg-danger">{{ $unreadNotifications }}</span>@endif</a>
                    @if (auth()->user()->role === 'admin')<a class="ps-header-action" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i><span class="d-none d-lg-inline">Admin</span></a>@endif
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf<button class="ps-header-action border-0 bg-transparent" type="submit"><i class="bi bi-box-arrow-right"></i><span class="d-none d-lg-inline">Đăng xuất</span></button></form>
                @else
                    <a class="ps-header-action" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i><span class="d-none d-lg-inline">Đăng nhập</span></a>
                    <a class="ps-header-action" href="{{ route('register') }}"><i class="bi bi-person-plus"></i><span class="d-none d-lg-inline">Đăng ký</span></a>
                @endauth
                <a class="ps-header-action position-relative" href="{{ auth()->check() ? route('wishlist.index') : route('login', ['redirect' => url()->current()]) }}"><i class="bi bi-heart"></i><span class="d-none d-lg-inline">Yêu thích</span>@auth @php $wishlistCount = auth()->user()->wishlist()->count(); @endphp @if($wishlistCount)<span class="badge rounded-pill bg-danger" data-wishlist-count>{{ $wishlistCount }}</span>@endif @endauth</a>
                <a class="ps-header-action position-relative" href="{{ auth()->check() ? route('cart.index') : route('login') }}"><i class="bi bi-cart3"></i><span class="d-none d-lg-inline">Giỏ hàng</span>@auth @php $cartCount = auth()->user()->carts()->with('items')->get()->flatMap->items->sum('quantity'); @endphp @if($cartCount)<span class="badge rounded-pill bg-danger">{{ $cartCount }}</span>@endif @endauth</a>
            </div>
        </div>
        <form class="ps-search ps-live-search d-flex d-md-none mt-3" action="{{ route('products.index') }}" method="GET" role="search" data-suggest-url="{{ route('products.suggest') }}">
            <div class="position-relative flex-grow-1"><input class="form-control" type="search" name="q" placeholder="Tìm kiếm sản phẩm" aria-label="Tìm kiếm sản phẩm" autocomplete="off" aria-controls="ps-search-results-mobile" aria-expanded="false"><div id="ps-search-results-mobile" class="ps-search-results" role="listbox" aria-label="Gợi ý sản phẩm" hidden></div></div>
            <button class="btn ps-btn-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
</header>
