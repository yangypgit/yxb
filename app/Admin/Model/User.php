<?php

namespace App\Admin\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Model
{
    use Notifiable;

    protected $table = 'yxb_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'parent_id', 'userId', 'userName', 'password', 'name', 'contact', 'type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime',
    ];

    // 如果表中没有created_at 
    // public $timestamps = false;

    // 可获取表单提交过来的值$model
    public static function boot()
    {
        parent::boot();

        // dd(request()); exit;
        static::saving(function ($model) 
        {
            // echo 'saving ????'; exit;
        });

        static::deleted(function ($model) 
        {
            echo 'deleted ????'; exit;
        });
    }

    public static function incrementByUserId($field, $userId, $num = 1)
    {
        return static::where('userId', $userId)->increment($field, $num);
    }

    public static function decrementByUserId($field, $userId, $num = 1)
    {
        return static::where('userId', $userId)->decrement($field, $num);
    }
}
