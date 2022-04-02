<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */





    protected $fillable = [
        'name',
        'email',
        'password',
        'public',
        'private'
    ];




//    protected function setPublicAttribute($value)
//     {
//         $this->attributes['public'] = bcrypt($value);
//     }



    // public function setTheNameAttribute($value)
    //     {
    //         $this->attributes('name') = 0; //($value) ? $value : $default;
    //     //default is anything you want
    //     }

    // public function save(array $options = [])
    // {
    //     if (Auth::user()) {
    //         $this->public = 2;
    //         //you may use user's name or any other property
    //         $this->private = 4;
    //     }

    //     return parent::save();
    // }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
