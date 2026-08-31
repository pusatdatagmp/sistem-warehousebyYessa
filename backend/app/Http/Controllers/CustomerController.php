<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index() { return response()->json(\Illuminate\Support\Facades\Cache::remember('customers_all', 300, fn () => Customer::select('id', 'name', 'phone', 'address')->latest()->get())); }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Cache::forget('customers_all');
        return response()->json(Customer::create($request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
        ])), 201);
    }

    public function update(Request $request, Customer $customer)
    {
        \Illuminate\Support\Facades\Cache::forget('customers_all');
        $customer->update($request->validate(['name' => 'required|string|max:150', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:500']));
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        \Illuminate\Support\Facades\Cache::forget('customers_all');
        $customer->delete();
        return response()->noContent();
    }
}