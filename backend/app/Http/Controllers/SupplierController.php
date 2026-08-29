<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index() { return response()->json(Supplier::latest()->get()); }

    public function store(Request $request)
    {
        return response()->json(Supplier::create($request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
        ])), 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate(['name' => 'required|string|max:150', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:500']));
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->noContent();
    }
}