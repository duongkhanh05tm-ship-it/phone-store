<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View { return view('admin.banners.index', ['banners' => Banner::orderBy('position')->orderBy('sort_order')->orderBy('id')->paginate(15)]); }
    public function create(): View { return view('admin.banners.form', ['banner' => new Banner(), 'heading' => 'Thêm banner']); }
    public function store(BannerRequest $request): RedirectResponse { $banner = new Banner(); $this->save($request, $banner); return redirect()->route('admin.banners.index')->with('status', 'Đã thêm banner.'); }
    public function edit(Banner $banner): View { return view('admin.banners.form', ['banner' => $banner, 'heading' => 'Sửa banner']); }
    public function update(BannerRequest $request, Banner $banner): RedirectResponse { $this->save($request, $banner); return redirect()->route('admin.banners.index')->with('status', 'Đã cập nhật banner.'); }
    public function status(Banner $banner): RedirectResponse { $banner->update(['is_active' => !$banner->is_active]); return back()->with('status', 'Đã cập nhật trạng thái banner.'); }
    public function destroy(Banner $banner): RedirectResponse
    {
        $paths = array_filter([$banner->image_path, $banner->mobile_image_path]);
        $banner->delete();
        foreach ($paths as $path) if (str_starts_with($path, 'banners/')) Storage::disk('public')->delete($path);
        return back()->with('status', 'Đã xóa banner.');
    }
    private function save(BannerRequest $request, Banner $banner): void
    {
        $data = $request->validated();
        unset($data['image'], $data['mobile_image']);
        $data['is_active'] = $request->boolean('is_active');
        $old = array_filter([$banner->image_path, $banner->mobile_image_path]);
        foreach (['image' => 'image_path', 'mobile_image' => 'mobile_image_path'] as $input => $column) {
            if ($request->hasFile($input)) $data[$column] = $request->file($input)->store('banners', 'public');
        }
        $banner->fill($data)->save();
        foreach ($old as $path) if (str_starts_with($path, 'banners/') && !in_array($path, [$banner->image_path, $banner->mobile_image_path], true)) Storage::disk('public')->delete($path);
    }
}
