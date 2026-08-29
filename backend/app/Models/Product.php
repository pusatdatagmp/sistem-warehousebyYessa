<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'type', 'default_supplier', 'default_customer', 'unit', 'buy_price', 'sell_price', 'stock'];
    protected $casts = ['buy_price' => 'decimal:2', 'sell_price' => 'decimal:2', 'stock' => 'decimal:3'];
    protected $appends = ['status'];

    public function getStatusAttribute(): string
    {
        return $this->stock > 5 ? 'Tersedia' : ($this->stock > 0 ? 'Menipis' : 'Habis');
    }

    public function transactions() { return $this->hasMany(Transaction::class); }
}
