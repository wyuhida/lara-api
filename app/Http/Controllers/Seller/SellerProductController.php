<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Transformers\ProductTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SellerProductController extends ApiController
{
    
    public function __construct()
    {
        parent::__construct();
        $this->middleware('transform.input:' . ProductTransformer::class)->only(['store','update']);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Seller $seller)
    {
        $product = $seller->products;
        return $this->showAll($product);   
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $seller)
    {
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'quantity' => 'required|integer|min:1',
            'image' => 'required|image',
        ];
        $this->validate($request, $rules);

        $data = $request->all();
        $data['status'] = Product::UNAVAILABLE_PRODUCT;

        $file = $request->file('image');
        // $name = $file->getClientOriginalName();
        // $extension = $file->getClientOriginalExtension();
        $name = $file->hashName(); 
        $file->move(public_path('img'), $name);
        $data['image'] = $name;
        $data['seller_id'] = $seller->id;

        $product = Product::create($data);
        return $this->showOne($product);
    }

    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Seller  $seller
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Seller $seller, Product $product)
    {
        $rules = [
            'quantity' => 'integer|min:1',
            'status' => 'in:' . Product::AVAILABLE_PRODUCT . ',' . Product::UNAVAILABLE_PRODUCT,
            'image' => 'image'
        ];
        $this->validate($request, $rules);
        $this->checkSeller($seller,$product);

        $product->fill($request->only([
            'name',
            'description',
            'quantity'
        ]));

        if($request->has('status'))
        {
            $product->status = $request->status;
            if($product->isAvailable() && $product->categories()->count() == 0)
            {
                return $this->errorResponse('An active product must have at least one categories', 409);
            }
        }

        if($request->hasFile('image'))
        {
            $path = "img/". $product->image;
            File::delete($path);

            $file = $request->file('image');
            $name = $file->hashName(); 
            $file->move(public_path('img'), $name);
            $product['image'] = $name;

        }

        if($product->isClean())
        {
            return $this->errorResponse('You need to specify a different value to update', 422);
        }

        $product->save();
        return $this->showOne($product);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Seller  $seller
     * @return \Illuminate\Http\Response
     */
    public function destroy(Seller $seller, Product $product)
    {
        $this->checkSeller($seller, $product);
        $product->delete();

        $path = "img/". $product->image;
        if(File::exists($path)){
            File::delete($path);
        }

        return $this->showOne($product);
    }

    protected function checkSeller(Seller $seller, Product $product)
    {
        if($seller->id != $product->seller_id)
        {
            throw new HttpException(422,'the specified seller is not the actual seller of the products');
        }
    }
}
