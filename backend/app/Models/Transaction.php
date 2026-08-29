<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['type', 'product_id', 'admin_id', 'customer_name', 'supplier_name', 'unit', 'qty', 'buy_price', 'sell_price', 'total_price'];
    protected $casts = ['qty' => 'decimal:3', 'buy_price' => 'decimal:2', 'sell_price' => 'decimal:2', 'total_price' => 'decimal:2'];

    public function product() { return $this->belongsTo(Product::class); }
    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
