<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ProductReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'home'])->name('home');
Route::get('/products/filter-home', [ProductController::class, 'filterHome'])->name('products.filter-home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::get('/products/suggest', [ProductController::class, 'suggest'])->name('products.suggest');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/chat', [ChatController::class, 'store'])->middleware('throttle:chat')->name('chat.store');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout.index');
    Route::post('/checkout', [OrderController::class, 'place'])->name('checkout.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])->name('products.reviews.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'status'])->name('orders.status');
    Route::patch('orders/{order}/payment-status', [AdminOrderController::class, 'paymentStatus'])->name('orders.payment-status');
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::resource('promotions', PromotionController::class)->except(['show']);
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::patch('banners/{banner}/status', [BannerController::class, 'status'])->name('banners.status');
    Route::patch('promotions/{promotion}/status', [PromotionController::class, 'status'])->name('promotions.status');
    Route::patch('products/{product}/status', [AdminProductController::class, 'status'])->name('products.status');
    Route::post('products/{product}/variants', [AdminProductController::class, 'storeVariant'])->name('products.variants.store');
    Route::put('products/{product}/variants/{variant}', [AdminProductController::class, 'updateVariant'])->name('variants.update');
    Route::patch('products/{product}/variants/{variant}/status', [AdminProductController::class, 'variantStatus'])->name('variants.status');
    Route::post('products/{product}/images', [AdminProductController::class, 'storeImages'])->name('products.images.store');
    Route::patch('products/{product}/images/{image}/primary', [AdminProductController::class, 'primaryImage'])->name('images.primary');
    Route::patch('products/{product}/images/sort', [AdminProductController::class, 'sortImages'])->name('images.sort');
    Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->name('images.destroy');
    Route::get('{type}', [TaxonomyController::class, 'index'])->name('taxonomy.index');
    Route::get('{type}/create', [TaxonomyController::class, 'create'])->name('taxonomy.create');
    Route::post('{type}', [TaxonomyController::class, 'store'])->name('taxonomy.store');
    Route::get('{type}/{taxonomy}/edit', [TaxonomyController::class, 'edit'])->name('taxonomy.edit');
    Route::put('{type}/{taxonomy}', [TaxonomyController::class, 'update'])->name('taxonomy.update');
    Route::patch('{type}/{taxonomy}/status', [TaxonomyController::class, 'status'])->name('taxonomy.status');
})->whereIn('type', ['categories', 'brands']);

// Alias preview để kiểm tra giao diện, không đại diện cho chức năng mua hàng thực tế.
Route::get('/preview', [ProductController::class, 'home'])->name('preview');
