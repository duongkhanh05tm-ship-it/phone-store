<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model { protected $fillable=['user_id','customer_name','customer_phone','customer_email','shipping_address','subtotal','shipping_fee','discount_amount','total_amount','payment_method','payment_status','order_status','note']; protected $casts=['subtotal'=>'decimal:2','shipping_fee'=>'decimal:2','discount_amount'=>'decimal:2','total_amount'=>'decimal:2']; public function user(): BelongsTo { return $this->belongsTo(User::class); } public function items(): HasMany { return $this->hasMany(OrderItem::class); } }
