<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use App\Models\ProductVariantPrice;
use Illuminate\Database\Eloquent\Builder;

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
        // ________steps________//

        // 1. create product
        // Store Product
        // Store Images
        // Store Variants
        // Store Variant prices

        // 2. product variants
        // 3. product variants prices
        // 4. product images

        try {
            DB::transaction(function () use ($request) {
                $product = Product::create($request->only(['title', 'sku', 'description']));

                // store Images
                if ($request->hasFile('product_image')) {

                    foreach ($request->file('product_image') as $file) {

                        $filename = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                        $file->move(public_path('uploads/products/images'), $filename);

                        $product->images()->create([
                            'file_path' => $filename
                        ]);
                    }
                }


                foreach ($request->product_variant as $variant) {


                    foreach ($variant['tags'] as $tag) {
                        $product->variants()->create([
                            'variant' => $tag,
                            'variant_id' => $variant['option']
                        ]);
                    }
                }

                foreach ($request->product_variant_prices as $price) {
                    $priceObj = new ProductVariantPrice();

                    $attrs = explode("/", $price['title']);

                    $product_variant_ids = [];

                    for ($i = 0; $i < count($attrs) - 1; $i++) {
                        $product_variant_ids[] = ProductVariant::select('id')->where('variant', $attrs[$i])->latest()->first()->id;
                    }

                    $numberArr = [
                        1 => 'one',
                        2 => 'two',
                        3 => 'three',
                    ];

                    for ($i = 1; $i <= count($product_variant_ids); $i++) {
                        $priceObj->{'product_variant_' . $numberArr[$i]} = $product_variant_ids[$i - 1];
                    }

                    $priceObj->price = $price['price'];
                    $priceObj->stock = $price['stock'];
                    $priceObj->product_id = $product->id;

                    $priceObj->save();
                }
            });
        } catch (Exception $error) {
            return response($error, 500);
        }
        return response('Product has been added successfully');
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
        $product->load(['prices', 'variants']);
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
        try {

            DB::transaction(function () use ($product, $request) {

                // update common properties
                $product->update(['title' => $request->title, 'sku' => $request->sku, 'description' => $request->description]);

                // update images if have
                if ($request->hasFile('product_image')) {

                    // remove previous images
                    foreach ($product->images ?? [] as $img) {
                        if (file_exists(public_path('uploads/products/images/' . $img->file_path)))
                            unlink(public_path('uploads/products/images' . $img->file_path));
                    }

                    // delete all images data
                    $product->images()->delete();

                    // store new images
                    foreach ($request->file('product_image') as $file) {
                        $filename = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
                        $file->move(public_path('uploads/products/images'), $filename);

                        $product->images()->create([
                            'file_path' => $filename
                        ]);
                    }
                }


                // update variants
                foreach ($request->product_variant as $variant) {
                    $variant = json_decode($variant);
                    $product_variants = $product->variants;
                    $num_product_variants = $product_variants->count();

                    $num_tags = 0;
                    foreach ($variant->tags as $index => $tag) {
                        $num_tags += 1;
                        if ($num_product_variants >= $index + 1) {
                            $product_variants[$index]->update(['variant' => $tag]);
                        } else {
                            $product->variants()->create([
                                'variant' => $tag,
                                'variant_id' => $variant->option
                            ]);
                        }
                    }
                    // delete previous extra variants
                    for ($i = 1; $i <= $num_product_variants - $num_tags; $i++) {
                        $product_variants[$num_product_variants - $i]->delete();
                    }
                }

                // update combination
                $num_req_prices = 0;
                foreach ($request->product_variant_prices as $index => $price) {
                    $price = json_decode($price);
                    $attrs = explode("/", $price->title);
                    $product_variant_ids = [];

                    for ($i = 0; $i < count($attrs) - 1; $i++) {
                        $product_variant_ids[] = ProductVariant::select('id')->where('variant', $attrs[$i])->latest()->first()->id;
                    }

                    $new_pv_prices = new ProductVariantPrice();
                    $p_prices = $product->prices;
                    $num_pv_prices = count($p_prices);

                    $num_req_prices += 1;
                    $numArr = [
                        1 => 'one',
                        2 => 'two',
                        3 => 'three',
                    ];

                    if ($num_pv_prices >= $index + 1) {
                        for ($i = 1; $i <= count($product_variant_ids); $i++) {
                            $p_prices[$index]->{'product_variant_' . $numArr[$i]} = $product_variant_ids[$i - 1];
                        }
                        $p_prices[$index]->price = $price->price;
                        $p_prices[$index]->stock = $price->stock;
                        $p_prices[$index]->product_id = $product->id;
                        $p_prices[$index]->save();
                    } else {
                        for ($i = 1; $i <= count($product_variant_ids); $i++) {
                            $new_pv_prices->{'product_variant_' . $numArr[$i]} = $product_variant_ids[$i - 1];
                        }
                        $new_pv_prices->price = $price->price;
                        $new_pv_prices->stock = $price->stock;
                        $new_pv_prices->product_id = $product->id;
                        $new_pv_prices->save();
                    }
                }

                // remove previous combination
                for ($i = 1; $i <= $num_pv_prices - $num_req_prices; $i++) {
                    $p_prices[$num_pv_prices - $i]->delete();
                }
            });
        } catch (Exception $e) {
            return response($e->getMessage(), 422);
        }

        return response('Product has been updated successfully');
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
