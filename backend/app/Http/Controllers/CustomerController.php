<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index() { return response()->json(Customer::latest()->get()); }

    public function store(Request $request)
    {
        return response()->json(Customer::create($request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
        ])), 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->validate(['name' => 'required|string|max:150', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:500']));
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->noContent();
    }
}