<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\Request;

class SellerTransactionController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Seller $seller)
    {
        $transactions = $seller->products()
        // option 1
        ->whereHas('transactions')
        ->with('transactions')
        ->get()
        //option 2
        ->pluck('transactions')
        ->collapse();
    return $this->showAll($transactions);
    }

   
}
