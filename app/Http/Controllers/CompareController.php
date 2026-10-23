<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function index(Request $request): View
    {
        $rawIds = $request->input('ids', []);
        $rawIds = is_string($rawIds) ? explode(',', $rawIds) : (is_array($rawIds) ? $rawIds : []);
        $ids = collect($rawIds)->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT))->filter(fn ($id) => $id > 0)->unique()->take(3)->values();
        $requestedVariants = is_array($request->input('variants')) ? $request->input('variants') : [];
        $products = Product::query()->whereIn('id', $ids)->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))->whereHas('brand', fn ($q) => $q->where('is_active', true))
            ->whereHas('variants', fn ($q) => $q->where('is_active', true))
            ->with(['brand', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'), 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price')])->get()->sortBy(fn ($p) => $ids->search($p->id))->values();

        $data = $products->map(function ($product) use ($requestedVariants) {
            $variants = $product->variants->map(function ($variant) {
                $effective = $variant->sale_price !== null && $variant->sale_price < $variant->price ? $variant->sale_price : $variant->price;
                return ['id' => $variant->id, 'ram' => $variant->ram, 'storage' => $variant->storage, 'color' => $variant->color, 'price' => (float) $effective, 'original_price' => (float) $variant->price, 'stock' => $variant->stock_quantity];
            })->values();
            $selectedId = filter_var($requestedVariants[$product->id] ?? null, FILTER_VALIDATE_INT);
            $selected = array_key_exists($product->id, $requestedVariants)
                ? $variants->firstWhere('id', $selectedId)
                : ($variants->count() === 1 ? $variants->first() : null);
            $image = $product->images->first(); $specs = []; $raw = $product->specifications;
            $groups = is_array($raw) && !array_is_list($raw) ? [['rows' => collect($raw)->map(fn ($value, $name) => ['name' => $name, 'value' => $value])->values()->all()]] : ($raw ?: []);
            foreach ($groups as $group) foreach (($group['rows'] ?? []) as $row) if (is_array($row) && filled($row['name'] ?? null) && filled($row['value'] ?? null)) $specs[(string) $row['name']] = is_scalar($row['value']) ? (string) $row['value'] : json_encode($row['value'], JSON_UNESCAPED_UNICODE);
            return ['id' => $product->id, 'name' => $product->name, 'brand' => $product->brand->name, 'image' => $image ? (filter_var($image->image_path, FILTER_VALIDATE_URL) ? $image->image_path : Storage::url($image->image_path)) : asset('images/phonestore-placeholder.svg'), 'variants' => $variants, 'selected' => $selected, 'specs' => $specs];
        });
        return view('compare.index', ['products' => $data]);
    }
}

