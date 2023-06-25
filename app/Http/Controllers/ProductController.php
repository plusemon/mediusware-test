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



        $products = Product::query()->with('prices')

            // filter by title
            ->when(request('title'), function (Builder $query) {
                $keyword = request('title');
                $query->where('title', 'LIKE', "%$keyword%");
            })

            // filter by variation | variant, price
            ->whereHas('prices', function ($q) {

                $price_from = intval(request('price_from'));
                $price_to = intval(request('price_to'));
                $variant = request('variant');

                $q->when($price_from, function (Builder $query, $price_from) {
                    $query->where('price', '>=', $price_from);
                })->when($price_to, function (Builder $query, $price_to) {
                    $query->where('price', '<=', $price_to);
                })->when($variant, function (Builder $query, $variant) {
                    $query->where('product_variant_one', $variant)
                        ->orWhere('product_variant_two', $variant)
                        ->orWhere('product_variant_three', $variant);
                });
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
                'options' => $variant->options->unique('variant')->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'value' => $option->variant
                    ];
                })
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
