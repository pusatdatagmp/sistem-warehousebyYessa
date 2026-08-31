<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index() { return response()->json(\Illuminate\Support\Facades\Cache::remember('suppliers_all', 300, fn () => Supplier::select('id', 'name', 'phone', 'address')->latest()->get())); }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Cache::forget('suppliers_all');
        return response()->json(Supplier::create($request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
        ])), 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        \Illuminate\Support\Facades\Cache::forget('suppliers_all');
        $supplier->update($request->validate(['name' => 'required|string|max:150', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:500']));
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        \Illuminate\Support\Facades\Cache::forget('suppliers_all');
        $supplier->delete();
        return response()->noContent();
    }
}