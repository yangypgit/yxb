<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Facades\Voyager;

class YxbUser extends Model
{
    public static function findUserByUserName($username)
    {
        return static::where('userName', $username)->first();
    }
    
    public static function findUserById($id)
    {
        return static::where('id', $id)->first();
    }
}
