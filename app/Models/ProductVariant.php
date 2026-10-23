<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ProductVariant extends Model { protected $fillable=['product_id','sku','color','ram','storage','price','sale_price','stock_quantity','is_active']; protected $casts=['price'=>'decimal:2','sale_price'=>'decimal:2','stock_quantity'=>'integer','is_active'=>'boolean']; public function product(): BelongsTo { return $this->belongsTo(Product::class); } public function images(): HasMany { return $this->hasMany(ProductImage::class); } public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); } }
