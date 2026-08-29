<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->withSum(['transactions as total_masuk' => fn ($query) => $query->where('type', 'IN')], 'qty')
            ->withSum(['transactions as total_keluar' => fn ($query) => $query->where('type', 'OUT')], 'qty')
            ->withSum(['transactions as total_biaya_masuk' => fn ($query) => $query->where('type', 'IN')], 'total_price')
            ->withSum(['transactions as total_pendapatan_keluar' => fn ($query) => $query->where('type', 'OUT')], 'total_price')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->supplier, fn ($query, $supplier) => $query->where('default_supplier', $supplier))
            ->when($request->customer, fn ($query, $customer) => $query->where('default_customer', $customer))
            ->when(in_array($request->type, ['basah', 'kering'], true), fn ($query) => $query->where('type', $request->type))
            ->latest()
            ->paginate(20);

        $products->getCollection()->transform(function (Product $product) {
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

        return $products;
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
