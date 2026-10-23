<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\VariantRequest;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
class ProductController extends Controller
{
 public function index(Request $request): View { $products=Product::with(['brand','category','variants','images'])->when($request->filled('q'),fn($q)=>$q->where('name','like','%'.$request->string('q').'%'))->when($request->filled('brand_id'),fn($q)=>$q->where('brand_id',$request->integer('brand_id')))->when($request->filled('category_id'),fn($q)=>$q->where('category_id',$request->integer('category_id')))->latest()->paginate(10)->withQueryString(); return view('admin.products.index',['products'=>$products,'brands'=>Brand::orderBy('name')->get(),'categories'=>Category::orderBy('name')->get()]); }
 public function create(): View { return view('admin.products.create',['product'=>new Product(),'brands'=>Brand::where('is_active',true)->orderBy('name')->get(),'categories'=>Category::where('is_active',true)->orderBy('name')->get()]); }
 public function store(ProductRequest $request): RedirectResponse { $data=$request->validated(); $data['specifications']=$this->specifications($data['specifications']??null); $data['is_active']=$request->boolean('is_active'); $product=Product::create($data); return redirect()->route('admin.products.edit',$product)->with('status','Đã tạo sản phẩm.'); }
 public function edit(Product $product): View { $product->load(['variants','images']); return view('admin.products.edit',['product'=>$product,'brands'=>Brand::where('is_active',true)->orderBy('name')->get(),'categories'=>Category::where('is_active',true)->orderBy('name')->get()]); }
 public function update(ProductRequest $request, Product $product): RedirectResponse { $data=$request->validated(); $data['specifications']=$this->specifications($data['specifications']??null); $data['is_active']=$request->boolean('is_active'); $product->update($data); return back()->with('status','Đã cập nhật sản phẩm.'); }
 public function status(Product $product): RedirectResponse { $product->update(['is_active'=>!$product->is_active]); return back()->with('status',$product->is_active?'Đã kích hoạt sản phẩm.':'Đã ngừng kinh doanh sản phẩm.'); }
 public function storeVariant(VariantRequest $request, Product $product): RedirectResponse { $data=$request->validated(); $data['is_active']=$request->boolean('is_active'); $product->variants()->create($data); return back()->with('status','Đã thêm biến thể.'); }
 public function updateVariant(VariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse { abort_unless($variant->product_id === $product->id, 404); $data=$request->validated(); $data['is_active']=$request->boolean('is_active'); $variant->update($data); return back()->with('status','Đã cập nhật biến thể.'); }
 public function variantStatus(Product $product, ProductVariant $variant): RedirectResponse { abort_unless($variant->product_id === $product->id, 404); $variant->update(['is_active'=>!$variant->is_active]); return back()->with('status','Đã cập nhật trạng thái biến thể.'); }
 public function storeImages(ProductImageRequest $request, Product $product): RedirectResponse
 {
  $stored=[];
  $variantId=$request->input('product_variant_id');
  if ($variantId !== null) abort_unless($product->variants()->whereKey($variantId)->exists(), 422);
  try {
   DB::transaction(function () use ($request, $product, &$stored, $variantId) {
    $isPrimary=!$product->images()->exists(); $sort=(int) ($product->images()->max('sort_order') ?? -1)+1;
    foreach ($request->file('images') as $file) { $path=$file->store('products','public'); $stored[]=$path; $product->images()->create(['product_variant_id'=>$variantId,'image_path'=>$path,'is_primary'=>$isPrimary,'sort_order'=>$sort++]); $isPrimary=false; }
   });
  } catch (\Throwable $exception) { foreach ($stored as $path) Storage::disk('public')->delete($path); throw $exception; }
  return back()->with('status','Đã tải ảnh lên.');
 }
 public function primaryImage(Product $product, ProductImage $image): RedirectResponse { abort_unless($image->product_id === $product->id, 404); DB::transaction(function () use ($product, $image) { $product->images()->update(['is_primary'=>false]); $image->update(['is_primary'=>true]); }); return back()->with('status','Đã chọn ảnh đại diện.'); }
 public function sortImages(Request $request, Product $product): RedirectResponse { $data=$request->validate(['image_ids'=>'required|array','image_ids.*'=>'integer|distinct']); $ids=array_map('intval',$data['image_ids']); $images=$product->images()->whereIn('id',$ids)->get()->keyBy('id'); abort_unless($images->count() === count($ids) && count($ids) === $product->images()->count(), 422); DB::transaction(function () use ($ids, $images) { foreach($ids as $order=>$id) $images[$id]->update(['sort_order'=>$order]); }); return back()->with('status','Đã cập nhật thứ tự ảnh.'); }
 public function destroyImage(Product $product, ProductImage $image): RedirectResponse { abort_unless($image->product_id === $product->id, 404); $path=$image->image_path; abort_unless(is_string($path) && str_starts_with($path,'products/'), 422); DB::transaction(function () use ($product, $image) { $wasPrimary=$image->is_primary; $image->delete(); if($wasPrimary && ($next=$product->images()->first())) { $product->images()->update(['is_primary'=>false]); $next->update(['is_primary'=>true]); } }); if(!ProductImage::where('image_path',$path)->exists()) Storage::disk('public')->delete($path); return back()->with('status','Đã xóa ảnh.'); }
 private function specifications(mixed $value): ?array
 {
  if (!$value) return null;
  if (is_array($value) && array_is_list($value)) {
   return collect($value)->take(20)->map(function ($group) {
    return ['group' => trim((string) ($group['group'] ?? '')), 'rows' => collect($group['rows'] ?? [])->take(30)->map(fn ($row) => ['name' => trim((string) ($row['name'] ?? '')), 'value' => trim((string) ($row['value'] ?? ''))])->filter(fn ($row) => $row['name'] !== '' && $row['value'] !== '')->values()->all()];
   })->filter(fn ($group) => $group['group'] !== '' && $group['rows'])->values()->all();
  }
  if (is_string($value)) { $decoded=json_decode($value,true); if (is_array($decoded)) return $this->specifications($decoded); return collect(preg_split('/\r?\n/',$value))->filter()->map(fn($line)=>str_contains($line,':')?['name'=>trim(explode(':',$line,2)[0]),'value'=>trim(explode(':',$line,2)[1])]:['name'=>trim($line),'value'=>'Có'])->pipe(fn($rows)=>[['group'=>'Thông tin chung','rows'=>$rows->all()]]); }
  return null;
 }
}