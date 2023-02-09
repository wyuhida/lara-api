<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Helper\Tokenable;
use App\Transformers\UserTransformer;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Tokenable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    const VERIFIED_USER = '1';
    const UNVERIFIED_USER = '0';

    const ADMIN_USER = 'true';
    const REGULAR_USER = 'false';

    public $transformer = UserTransformer::class;
    
    protected $table = 'users';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'name',
        'email',
        'password',
        'verified',
        'verification_token',
        'admin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token'
    ];


    /**
     * Mutators
     */

     public function setNameAttribute($name)
     {
        $this->attributes['name'] = strtolower($name);
     }

     public function getNameAttribute($name)
     {
        return ucwords($name);
     }

     public function setEmailAttribute($email)
     {
        $this->attributes['email'] = strtolower($email);
     }


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isVerified()
    {
        return $this->verified == User::VERIFIED_USER;
    }

    public function isAdmin()
    {
        return $this->admin == User::ADMIN_USER;
    }

   
    // public function generateVerificationCode()
    // {
    //         Str::random(40);
    //     // $this->api_token = $token;
    //     // return $this;
    // }

    public static function generateVerificationCodes()
    {
        return Str::random(20);
    }


}
