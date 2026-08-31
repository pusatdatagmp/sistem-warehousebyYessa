<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json(
            \Illuminate\Support\Facades\Cache::remember('dashboard_stats', 300, function () {
                $transactions = Transaction::get();
                $lowStock = Product::where('stock', '<=', 5)->select('id', 'name', 'stock', 'unit')->get();
                return [
                    'income' => $transactions->where('type', 'OUT')->sum('total_price'),
                    'expense' => $transactions->where('type', 'IN')->sum('total_price'),
                    'profit' => $transactions->where('type', 'OUT')->sum(fn ($item) => ($item->sell_price - $item->buy_price) * $item->qty),
                    'incoming_qty' => $transactions->where('type', 'IN')->sum('qty'),
                    'outgoing_qty' => $transactions->where('type', 'OUT')->sum('qty'),
                    'low_stock' => $lowStock,
                ];
            })
        );
    }

    public function products(Request $request)
    {
        return Product::query()
            ->select('id', 'name', 'unit', 'stock', 'buy_price', 'sell_price', 'type')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);
    }

    public function transactions(Request $request)
    {
        $transactions = $this->filtered($request)
            ->with(['product:id,name,unit', 'admin:id,name'])
            ->select('id', 'type', 'product_id', 'admin_id', 'customer_name', 'supplier_name', 'qty', 'buy_price', 'sell_price', 'total_price', 'created_at')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $transactions,
            'stats' => $this->stats($request),
        ]);
    }

    public function storeIn(Request $request)
    {
        $data = $request->validate(['supplier_name' => 'required|string|max:150', 'product_name' => 'required|string|max:150', 'unit' => 'required|string|max:30', 'buy_price' => 'required|numeric|min:0', 'qty' => 'required|numeric|min:0.001']);
        return DB::transaction(function () use ($data, $request) {
            $product = Product::withTrashed()->where('name', $data['product_name'])->first();
            if ($product?->trashed()) $product->restore();
            $product ??= Product::create(['name' => $data['product_name'], 'unit' => $data['unit'], 'buy_price' => $data['buy_price'], 'sell_price' => 0, 'stock' => 0]);
            $product->update(['unit' => $data['unit'], 'buy_price' => $data['buy_price'], 'default_supplier' => $data['supplier_name'], 'stock' => $product->stock + $data['qty']]);
            $transaction = Transaction::create(['type' => 'IN', 'product_id' => $product->id, 'admin_id' => $request->user()->id, 'supplier_name' => $data['supplier_name'], 'unit' => $data['unit'], 'qty' => $data['qty'], 'buy_price' => $data['buy_price'], 'total_price' => $data['buy_price'] * $data['qty']]);
            return response()->json($transaction->load('product'), 201);
        });
    }

    public function storeOut(Request $request)
    {
        $data = $request->validate(['customer_name' => 'required|string|max:150', 'product_id' => 'required|exists:products,id', 'sell_price' => 'required|numeric|min:0', 'qty' => 'required|numeric|min:0.001']);
        return DB::transaction(function () use ($data, $request) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            if ($data['qty'] > $product->stock) return response()->json(['message' => 'Stok tidak cukup'], 422);
            $product->decrement('stock', $data['qty']);
            $product->update(['sell_price' => $data['sell_price'], 'default_customer' => $data['customer_name']]);
            return response()->json(Transaction::create(['type' => 'OUT', 'product_id' => $product->id, 'admin_id' => $request->user()->id, 'customer_name' => $data['customer_name'], 'unit' => $product->unit, 'qty' => $data['qty'], 'buy_price' => $product->buy_price, 'sell_price' => $data['sell_price'], 'total_price' => $data['sell_price'] * $data['qty']])->load('product'), 201);
        });
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filtered($request)->with('product')->latest()->get();
        return response()->streamDownload(function () use ($rows) { $handle = fopen('php://output', 'w'); fputcsv($handle, ['Waktu', 'Tipe', 'Barang', 'Qty', 'Harga Beli', 'Harga Jual', 'Total']); foreach ($rows as $row) fputcsv($handle, [$row->created_at, $row->type, $row->product->name, $row->qty, $row->buy_price, $row->sell_price, $row->total_price]); fclose($handle); }, 'laporan-transaksi.csv', ['Content-Type' => 'text/csv']);
    }

    private function filtered(Request $request) { return Transaction::query()->when($request->start_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))->when($request->end_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date)); }
    private function stats(Request $request) { $items = $this->filtered($request)->get(); return ['income' => $items->where('type', 'OUT')->sum('total_price'), 'expense' => $items->where('type', 'IN')->sum('total_price'), 'profit' => $items->where('type', 'OUT')->sum(fn ($item) => ($item->sell_price - $item->buy_price) * $item->qty)]; }
}
