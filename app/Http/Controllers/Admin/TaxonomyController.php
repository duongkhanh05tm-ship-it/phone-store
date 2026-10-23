<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxonomyRequest;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
class TaxonomyController extends Controller
{
 public function index(string $type): View { abort_unless(in_array($type,['categories','brands'],true),404); $model=$type==='brands'?Brand::class:Category::class; return view('admin.taxonomy.index',['type'=>$type,'items'=>$model::withCount('products')->orderBy('name')->paginate(10),'item'=>null]); }
 public function create(string $type): View { abort_unless(in_array($type,['categories','brands'],true),404); return view('admin.taxonomy.form',['type'=>$type,'item'=>null]); }
 public function store(TaxonomyRequest $request,string $type): RedirectResponse { abort_unless(in_array($type,['categories','brands'],true),404); $model=$type==='brands'?new Brand:new Category; $data=$request->validated(); if($type==='brands'&&$request->hasFile('logo'))$data['logo']=$request->file('logo')->store('brands','public'); $model->fill($data)->save(); return redirect()->route('admin.taxonomy.index',$type)->with('status','Đã tạo '.($type==='brands'?'thương hiệu.':'danh mục.')); }
 public function edit(string $type,int $taxonomy): View { abort_unless(in_array($type,['categories','brands'],true),404); $model=$type==='brands'?Brand::class:Category::class; return view('admin.taxonomy.form',['type'=>$type,'item'=>$model::findOrFail($taxonomy)]); }
 public function update(TaxonomyRequest $request,string $type,int $taxonomy): RedirectResponse { abort_unless(in_array($type,['categories','brands'],true),404); $model=$type==='brands'?Brand::class:Category::class; $item=$model::findOrFail($taxonomy); $data=$request->validated(); if($type==='brands'&&$request->hasFile('logo')){if($item->logo)Storage::disk('public')->delete($item->logo);$data['logo']=$request->file('logo')->store('brands','public');} $item->update($data); return redirect()->route('admin.taxonomy.index',$type)->with('status','Đã cập nhật.'); }
 public function status(string $type,int $taxonomy): RedirectResponse { abort_unless(in_array($type,['categories','brands'],true),404); $model=$type==='brands'?Brand::class:Category::class; $item=$model::findOrFail($taxonomy); $item->update(['is_active'=>!$item->is_active]); return back()->with('status','Đã cập nhật trạng thái.'); }
}