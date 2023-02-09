<?php

namespace App\Providers;

use App\Mail\UserCreated;
use App\Mail\UserMailChanged;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
 
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        //send email user created
        User::created(function($user){
            retry(5, function() use ($user){
                Mail::to($user->email)->send(new UserCreated($user));
            },100);
           
        });

        //updated email 
        User::updated(function($user){
           if($user->isDirty('email'))
           {
            retry(5, function() use ($user){
                Mail::to($user)->send(new UserMailChanged($user));
            },100);
           
           }
        });

        //update stock 
        Product::updated(function($product){
            if($product->quantity == 0 && $product->isAvailable())
            {
                $product->status = Product::UNAVAILABLE_PRODUCT;
                $product->save();
            }
        });
    }
}
