<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Transaction;
use App\Scopes\BuyerScope;
use App\Transformers\BuyerTransformer;

class Buyer extends User
{
    use HasFactory;

    public $transformer = BuyerTransformer::class;


    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function booted()
    {
        parent::booted();
        static::addGlobalScope(new BuyerScope);
    }
  
}
