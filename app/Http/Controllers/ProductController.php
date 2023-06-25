<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $products = Product::query()

            // filter by title
            ->when(request('title'), function (Builder $query) {
                $keyword = request('title');
                $query->where('title', 'LIKE', "%$keyword%");
            })

            // filter by variation -------------------not completed------------------xx
            ->when(request('variation'), function (Builder $query) {
                $query;
            })

            // filter by price range -------------------not completed------------------xx
            ->when(request('price_from') or request('price_to'), function (Builder $query) {
                $price_from = request('price_from');
                $price_to = request('price_to');
                // $query->whereBetween('price', [$price_from, $price_to]);
            })

            // filter by date
            ->when(request('date'), function (Builder $query) {
                $keyword = request('date');
                $query->whereDate('created_at', $keyword);
            })
            ->paginate(2);

        // filtering view datas
        $variants = Variant::with('options')->get()->map(function ($variant) {
            return [
                'name' => $variant->title,
                'options' => $variant->options->unique('variant')->pluck('variant')
            ];
        });

        return view('products.index', compact('products', 'variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // create product
        // product variants
        // product variants prices
        // product images
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants', 'product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
