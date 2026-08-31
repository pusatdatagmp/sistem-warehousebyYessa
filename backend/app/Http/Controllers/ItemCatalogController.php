<?php

namespace App\Http\Controllers;

use App\Models\ItemCatalog;
use Illuminate\Http\Request;

class ItemCatalogController extends Controller
{
    public function index() { return response()->json(\Illuminate\Support\Facades\Cache::remember('catalogs_all', 300, fn () => ItemCatalog::select('id', 'name', 'unit', 'type')->latest()->get())); }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Cache::forget('catalogs_all');
        return response()->json(ItemCatalog::create($request->validate([
            'name' => 'required|string|max:150',
            'unit' => 'required|string|max:30',
            'type' => 'required|in:basah,kering',
        ])), 201);
    }

    public function update(Request $request, ItemCatalog $itemCatalog)
    {
        \Illuminate\Support\Facades\Cache::forget('catalogs_all');
        $itemCatalog->update($request->validate(['name' => 'required|string|max:150', 'unit' => 'required|string|max:30', 'type' => 'required|in:basah,kering']));
        return response()->json($itemCatalog);
    }

    public function destroy(ItemCatalog $itemCatalog)
    {
        \Illuminate\Support\Facades\Cache::forget('catalogs_all');
        $itemCatalog->delete();
        return response()->noContent();
    }
}