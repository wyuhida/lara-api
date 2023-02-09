<?php
namespace App\Helper;

use Illuminate\Support\Str;

Trait Tokenable 
{
    public function generateVerificationCode()
    {
        $token = Str::random(40);
        $this->verification_token = $token;
        $this->save();

        return $this;
    }
}