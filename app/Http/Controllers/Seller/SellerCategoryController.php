<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\Request;

class SellerCategoryController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Seller $seller)
    {
       $categories = $seller->products()
       //option 1
        ->whereHas('categories')
        ->with('categories')
        ->get()
        //option 2
        ->pluck('categories')
        ->collapse()
        ->unique('id')
        ->values();
        
        return $this->showAll($categories);
    }

   
}
