<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Product;
use App\Scopes\SellerScope;
use App\Transformers\SellerTransformer;

class Seller extends User
{
    use HasFactory;
    public $transformer = SellerTransformer::class;

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted()
    {
        parent::booted();
        static::addGlobalScope(new SellerScope);
    }
}
