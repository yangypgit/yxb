<?php

namespace App\Admin\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class UserTask extends Model
{
    use Notifiable;

    protected $table = 'yxb_user_task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'task_id', 'unit_price',
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

    // 如果表中没有created_at 设置为false 
    public $timestamps = true;
    // 日期存储格式
    protected $dateFormat = 'U';

    // 可获取表单提交过来的值$model
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) 
        {
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
