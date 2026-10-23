<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model { protected $fillable=['category_id','brand_id','name','slug','description','specifications','is_active']; protected $casts=['specifications'=>'array','is_active'=>'boolean']; public function category(): BelongsTo { return $this->belongsTo(Category::class); } public function brand(): BelongsTo { return $this->belongsTo(Brand::class); } public function variants(): HasMany { return $this->hasMany(ProductVariant::class); } public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id'); } public function promotions(): HasMany { return $this->hasMany(Promotion::class)->orderBy('sort_order')->orderBy('id'); }
    public function wishlists(): HasMany { return $this->hasMany(Wishlist::class); }
    public function reviews(): HasMany { return $this->hasMany(ProductReview::class); }
}
