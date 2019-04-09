<?php

namespace App\Admin\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Task extends Model
{
    use Notifiable;

    protected $table = 'yxb_tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'icon', 'type', 'link', 'user_name', 'id_card', 
        'phone', 'picture', 'describe', 'status',
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

    public function userTask()
    {
        return $this->hasMany(UserTask::class, 'task_id')->where('user_id', 1001);
    }
}
