<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantPrice extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function variantOne(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_one');
    }
    public function variantTwo(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_two');
    }
    public function variantThree(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_three');
    }
}
