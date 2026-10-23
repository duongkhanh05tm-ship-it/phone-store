<?php

namespace App\Http\Controllers;

use App\Models\{Banner, Brand, Category, Product};
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    private function baseQuery()
    {
        return Product::query()->where('products.is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->whereHas('brand', fn ($q) => $q->where('is_active', true))
            ->whereHas('variants', fn ($q) => $q->where('is_active', true))
            ->with(['brand', 'category', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'), 'variants' => fn ($q) => $q->where('is_active', true)->with(['images' => fn ($images) => $images->orderByDesc('is_primary')->orderBy('sort_order')])->orderBy('price')]);
    }

    private function homeCatalog(?int $brandId): array
    {
        $query = $this->baseQuery()->when($brandId, fn ($q) => $q->where('brand_id', $brandId));
        return [
            'products' => (clone $query)->latest()->limit(8)->get(),
            'saleProducts' => (clone $query)->whereHas('variants', fn ($q) => $q->where('is_active', true)->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price'))->latest()->limit(4)->get(),
            'newProducts' => (clone $query)->latest()->limit(4)->get(),
        ];
    }

    public function home(Request $request): View
    {
        $request->validate(['brand_id' => ['nullable', 'integer']]);
        return view('home', array_merge($this->homeCatalog($request->integer('brand_id')), [
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'selectedBrand' => $request->integer('brand_id'),
            'mainBanners' => Banner::currentlyActive()->where('position', 'main')->orderBy('sort_order')->orderBy('id')->get(),
            'sideBanners' => Banner::currentlyActive()->where('position', 'side')->orderBy('sort_order')->orderBy('id')->limit(2)->get(),
        ]));
    }

    public function filterHome(Request $request)
    {
        $request->validate(['brand_id' => ['nullable', 'integer']]);
        return response()->view('partials.home-products', array_merge($this->homeCatalog($request->integer('brand_id')), [
            'selectedBrand' => $request->integer('brand_id'),
        ]));
    }

    public function suggest(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $products = $this->baseQuery()
            ->where(function ($query) use ($term) {
                $query->where('products.name', 'like', '%'.$term.'%')
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', '%'.$term.'%'));
            })
            ->latest('products.id')
            ->limit(6)
            ->get();

        return response()->json(['data' => $products->map(function ($product) {
            $variant = $product->variants->sortBy(fn ($item) => (float) ($item->sale_price !== null && $item->sale_price < $item->price ? $item->sale_price : $item->price))->first();
            $image = $product->images->first();
            return [
                'name' => $product->name,
                'brand' => $product->brand->name,
                'url' => route('products.show', $product->slug),
                'image' => $image ? (filter_var($image->image_path, FILTER_VALIDATE_URL) ? $image->image_path : \Illuminate\Support\Facades\Storage::url($image->image_path)) : asset('images/phonestore-placeholder.svg'),
                'price' => (float) ($variant?->sale_price !== null && $variant->sale_price < $variant->price ? $variant->sale_price : $variant?->price),
                'original_price' => $variant?->sale_price !== null && $variant->sale_price < $variant->price ? (float) $variant->price : null,
            ];
        })->values()]);
    }

    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100'], 'brand_id' => ['nullable', 'integer'], 'category_id' => ['nullable', 'integer'], 'sort' => ['nullable', 'in:newest,price_asc,price_desc']]);
        $query = $this->baseQuery()->when($request->filled('q'), fn ($q) => $q->where('products.name', 'like', '%'.$request->string('q').'%'))
            ->when($request->integer('brand_id'), fn ($q) => $q->where('brand_id', $request->integer('brand_id')))
            ->when($request->integer('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')));
        $sort = $request->input('sort', 'newest');
        $sort === 'newest' ? $query->latest() : $query->orderByRaw($sort === 'price_asc' ? '(select min(coalesce(sale_price, price)) from product_variants where product_id = products.id and is_active = 1) asc' : '(select min(coalesce(sale_price, price)) from product_variants where product_id = products.id and is_active = 1) desc');
        return view('products.index', ['products' => $query->paginate(12)->withQueryString(), 'brands' => Brand::where('is_active', true)->orderBy('name')->get(), 'categories' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    public function show(string $slug): View
    {
        $product = $this->baseQuery()->with(['promotions' => fn ($q) => $q->where('is_active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->orderBy('sort_order')->orderBy('id')])->where('slug', $slug)->firstOrFail();
        $reviewFilter = request()->integer('rating');
        $reviews = $product->reviews()->with('user')->when($reviewFilter >= 1 && $reviewFilter <= 5, fn ($q) => $q->where('rating', $reviewFilter))->latest()->paginate(8)->withQueryString();
        $reviewStats = array_fill(1, 5, 0);
        $product->reviews()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->get()
            ->each(fn ($stat) => $reviewStats[(int) $stat->rating] = (int) $stat->total);
        $reviewTotal = (int) $product->reviews()->count();
        $reviewAverage = $reviewTotal ? round((float) $product->reviews()->avg('rating'), 1) : 0;
        $reviewDistribution = collect(range(5, 1))->map(fn ($rating) => [
            'rating' => $rating,
            'count' => $reviewStats[$rating],
            'percentage' => $reviewTotal ? ($reviewStats[$rating] / $reviewTotal * 100) : 0,
        ]);
        $canReview = auth()->check() && $product->variants()->whereHas('orderItems', fn ($query) => $query->whereHas('order', fn ($order) => $order->where('user_id', auth()->id())->where('order_status', 'completed')))->exists();
        $alreadyReviewed = auth()->check() && $product->reviews()->where('user_id', auth()->id())->exists();
        $related = $this->baseQuery()
            ->where('products.category_id', $product->category_id)
            ->where('products.id', '<>', $product->id)
            ->latest('products.id')
            ->limit(4)
            ->get();

        return view('products.show', compact('product', 'related', 'reviews', 'reviewStats', 'reviewDistribution', 'reviewTotal', 'reviewAverage', 'reviewFilter', 'canReview', 'alreadyReviewed'));
    }
}

// Storefront only reads active catalog data; checkout is intentionally not implemented yet.
