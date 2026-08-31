<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'products_' . md5(json_encode($request->all()));
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) return $cached;
        
        $query = Product::query()
            ->selectRaw('
                products.*,
                COALESCE(in_qty.total, 0) as total_masuk,
                COALESCE(out_qty.total, 0) as total_keluar,
                COALESCE(in_price.total, 0) as total_biaya_masuk,
                COALESCE(out_price.total, 0) as total_pendapatan_keluar
            ')
            ->leftJoinSub(
                \App\Models\Transaction::where('type', 'IN')->selectRaw('product_id, SUM(qty) as total')->groupBy('product_id'),
                'in_qty',
                'products.id',
                '=',
                'in_qty.product_id'
            )
            ->leftJoinSub(
                \App\Models\Transaction::where('type', 'OUT')->selectRaw('product_id, SUM(qty) as total')->groupBy('product_id'),
                'out_qty',
                'products.id',
                '=',
                'out_qty.product_id'
            )
            ->leftJoinSub(
                \App\Models\Transaction::where('type', 'IN')->selectRaw('product_id, SUM(total_price) as total')->groupBy('product_id'),
                'in_price',
                'products.id',
                '=',
                'in_price.product_id'
            )
            ->leftJoinSub(
                \App\Models\Transaction::where('type', 'OUT')->selectRaw('product_id, SUM(total_price) as total')->groupBy('product_id'),
                'out_price',
                'products.id',
                '=',
                'out_price.product_id'
            )
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->supplier, fn ($q, $supplier) => $q->where('default_supplier', $supplier))
            ->when($request->customer, fn ($q, $customer) => $q->where('default_customer', $customer))
            ->when(in_array($request->type, ['basah', 'kering'], true), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(10);

        $query->getCollection()->transform(function (Product $product) {
            $totalMasuk = (float) ($product->total_masuk ?? 0);
            $totalKeluar = (float) ($product->total_keluar ?? 0);
            $sisa = $totalMasuk - $totalKeluar;
            $avgBuyPrice = $totalMasuk > 0 ? (float) $product->total_biaya_masuk / $totalMasuk : 0;
            $avgSellPrice = $totalKeluar > 0 ? (float) $product->total_pendapatan_keluar / $totalKeluar : 0;

            return $product->setAttribute('total_masuk', $totalMasuk)
                ->setAttribute('total_keluar', $totalKeluar)
                ->setAttribute('sisa', $sisa)
                ->setAttribute('avg_buy_price', $avgBuyPrice)
                ->setAttribute('avg_sell_price', $avgSellPrice)
                ->setAttribute('harga_beli_keseluruhan', $sisa * $avgBuyPrice)
                ->setAttribute('harga_jual_keseluruhan', $sisa * $avgSellPrice)
                ->setAttribute('laba_keuntungan', $totalKeluar * ($avgSellPrice - $avgBuyPrice));
        });

        \Illuminate\Support\Facades\Cache::put($cacheKey, $query, 300);
        return $query;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'default_supplier' => 'nullable|string|max:150',
            'default_customer' => 'nullable|string|max:150',
            'name' => 'required|string|max:150|unique:products,name',
            'type' => 'required|in:basah,kering',
            'unit' => 'required|string|max:30',
            'stock' => 'required|numeric|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
        ]);

        return response()->json(Product::create($data), 201);
    }
}
