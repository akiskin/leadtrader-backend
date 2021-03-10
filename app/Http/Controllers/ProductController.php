<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return \App\Http\Resources\Product::collection(Product::all());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|min:5']);

        $name = $request->input('name');

        $product = new Product();

        $product->fill([
            'name' => $name
        ]);

        $product->save();

        return \App\Http\Resources\Product::make($product);
    }
}
