<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

const CHAT_PRODUCT_LIMIT = 6;
const CHAT_VARIANT_LIMIT = 4;
const CHAT_CONTEXT_LIMIT = 4;

 
class ProductRecommendationService
{
    public function find(string $message, int $limit = 6): Collection
    {
        $terms = preg_split('/\s+/u', mb_strtolower($message), -1, PREG_SPLIT_NO_EMPTY);
        $products = Product::query()->with(['brand', 'category', 'variants', 'images'])
            ->where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('is_active', true))
            ->get();

        $budget = $this->budget($message);
        return $products->map(function (Product $product) use ($terms, $budget) {
            $haystack = mb_strtolower($product->name.' '.($product->brand?->name ?? '').' '.($product->category?->name ?? '').' '.json_encode($product->specifications, JSON_UNESCAPED_UNICODE));
            $score = collect($terms)->sum(fn ($term) => mb_strlen($term) > 2 && str_contains($haystack, $term) ? 2 : 0);
            $variants = $product->variants->where('is_active', true)->values();
            if ($budget !== null) $score += $variants->contains(fn ($v) => (float) ($v->sale_price ?? $v->price) <= $budget) ? 4 : -2;
            return ['product' => $product, 'variants' => $variants, 'available' => $variants->where('stock_quantity', '>', 0), 'score' => $score];
        })->sortByDesc('score')->take(min($limit, CHAT_PRODUCT_LIMIT))->values();
    }

    private function budget(string $message): ?float
    {
        $text = mb_strtolower($message);
        if (!preg_match('/(?:dưới|duoi|tầm|tam|khoảng|khoang|ngân sách|ngan sach)?\s*(\d+(?:[.,]\d+)?)\s*(triệu|trieu|tr|m|m\b)/u', $text, $match)) return null;
        return (float) str_replace(',', '.', $match[1]) * 1000000;
    }

    public function context(Collection $matches): array
    {
        return $matches->take(CHAT_CONTEXT_LIMIT)->map(function ($item) {
            $product = $item['product'];
            return ['name' => $product->name, 'brand' => $product->brand?->name, 'category' => $product->category?->name,
                'url' => route('products.show', $product->slug), 'specifications' => $product->specifications,
                'variants' => $item['variants']->take(CHAT_VARIANT_LIMIT)->map(fn ($v) => ['ram' => $v->ram, 'storage' => $v->storage, 'color' => $v->color, 'price' => (float) $v->price, 'sale_price' => $v->sale_price !== null ? (float) $v->sale_price : null, 'stock' => (int) $v->stock_quantity])->values()->all()];
        })->values()->all();
    }

    public function cards(Collection $matches): array
    {
        return $matches->take(CHAT_PRODUCT_LIMIT)->map(function ($item) {
            $product = $item['product']; $variants = $item['variants'];
            $prices = $variants->map(fn ($v) => (float) ($v->sale_price ?? $v->price));
            $image = $product->images->first();
            return ['name' => $product->name, 'url' => route('products.show', $product->slug),
                'image' => $image ? Storage::url($image->image_path) : asset('images/phonestore-placeholder.svg'),
                'price' => $prices->min(), 'in_stock' => $item['available']->isNotEmpty(),
                'specifications_available' => !empty($product->specifications)];
        })->values()->all();
    }
}
