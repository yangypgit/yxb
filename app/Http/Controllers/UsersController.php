<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Http\Model\YxbUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UsersController extends BaseController
{
    /*  获取用户信息  */
    public function user_info(Request $request)
    {
        $this->recv_message('user_info()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $token = $request->input('token');
        if (!is_numeric($id) || !is_string($token))
        {
            Log::error('Parameter type error!');
            $result = $this->set_message(401, '参数类型错误！', 'Parameter type error!');
            return $result;
        }

        // token认证
        $user = $this->attestation($token, $id);
        if (!$user)
        {
            $result = $this->set_message(666, 'api 认证失败！');
            return $result;
        }
        $data['user_info'] = $user;

        // 已通过订单
        $ok_order = DB::table('yxb_user_task')
            ->where('user_id', $id)
            ->sum('finished');
        $data['ok_order'] = $ok_order;

        // 未通过订单
        $err_order = DB::table('yxb_user_task')
            ->where('user_id', $id)
            ->sum('fail');
        $data['err_order'] = $err_order;

        // 已结算订单
        $userObj = DB::table('yxb_users')
            ->where(['id' => $id])
            ->orWhere('parent_id', $id)
            ->select('id');
        $settle_accounts = DB::table('yxb_order_form')
            ->joinSub($userObj, 'user_obj', function ($join) 
            {
                $join->on('yxb_order_form.user_id', '=', 'user_obj.id');
            })
            ->where(['status' => 1, 'state' => 1])
            ->count();
        $data['settle_accounts'] = $settle_accounts;

        // 未结算订单
        $no_settlement = DB::table('yxb_order_form')
            ->joinSub($userObj, 'user_obj', function ($join) 
            {
                $join->on('yxb_order_form.user_id', '=', 'user_obj.id');
            })
            ->where(['status' => 1, 'state' => 0])
            ->count();
        $data['no_settlement'] = $no_settlement;

        // 公司的员工人数
        $data['staff'] = 0;
        $ret = Redis::hget('yxb_hregister_index', $user->userId);
        if ($ret)
        {
            $data['staff'] = $ret;
        }

        Log::info('User: ' . $id . ' 获取用户信息成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /*  获取该用户员工信息  */
    public function get_staff_info(Request $request)
    {
        $this->recv_message('get_staff_info()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $token = $request->input('token');
        $nowPage = $request->input('nowPage');
        if (!is_numeric($id) || !is_string($token))
        {
            Log::error('Parameter type error!');
            $result = $this->set_message(401, '参数类型错误！', 'Parameter type error!');
            return $result;
        }

        // token认证
        $user = $this->attestation($token, $id);
        if (!$user)
        {
            $result = $this->set_message(666, 'api 认证失败！');
            return $result;
        }

        // 分页查询
        if (!is_numeric($nowPage))                                                                                                                                                                            
        {                                                                                             
            $nowPage = 0;                                                                             
        }                                                                                             
        $limit = 10 * $nowPage;

        $user_list = [];
        $sql = "select u.id, u.userId, u.name, u.avatar, u.contact, sum(t.finished) finished 
                    from yxb_users u left join yxb_user_task t 
                    on u.id = t.user_id 
                    where u.parent_id = $id
                    group by u.id, u.userId, u.name, u.avatar, u.contact";
        $user_list_obj = DB::table(DB::raw("($sql) as t"))
            ->offset($limit)
            ->limit(10)
            ->get();
        if ($user_list_obj->isEmpty())
        {
            // 没有更多数据
            Log::info('User: ' . $id . ' 没有更多数据!');
            $result = $this->set_message(210, '没有更多数据');
            return $result;
        }
        $user_list = $user_list_obj->toArray();

        $data['user_list'] = $user_list;

        Log::info('User: ' . $id . ' 获取员工信息成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /*  获取该用户员工信息  */
    public function earnings(Request $request)
    {
        $this->recv_message('get_staff_info()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $token = $request->input('token');
        $nowPage = $request->input('nowPage');
        if (!is_numeric($id) || !is_string($token))
        {
            Log::error('Parameter type error!');
            $result = $this->set_message(401, '参数类型错误！', 'Parameter type error!');
            return $result;
        }

        // token认证
        $user = $this->attestation($token, $id);
        if (!$user)
        {
            $result = $this->set_message(666, 'api 认证失败！');
            return $result;
        }

        // 分页查询
        if (!is_numeric($nowPage))                                                                                                                                                                            
        {                                                                                             
            $nowPage = 0;                                                                             
        }                                                                                             
        $limit = 10 * $nowPage;

        // 任务ID和名称
        $tasks = [];
        $tasks_obj = DB::table('yxb_user_task')
            ->join('yxb_tasks', 'yxb_user_task.task_id', '=', 'yxb_tasks.id')
            ->where('user_id', $id)
            ->select('yxb_tasks.id', 'yxb_tasks.name')
            ->get();
        if (!$tasks_obj->isEmpty())
        {
            $tasks = $tasks_obj->toArray();
            $tasks = array_column($tasks, 'name', 'id');
        }
        $data['task_list'] = $tasks;

        $orders = [];
        $userObj = DB::table('yxb_users')
            ->where(['id' => $id])
            ->orWhere('parent_id', $id)
            ->select('id');
        $order_obj = DB::table('yxb_order_form')
            ->joinSub($userObj, 'user_obj', function ($join) 
            {
                $join->on('yxb_order_form.user_id', '=', 'user_obj.id');
            })
            ->where(['status' => 1, 'state' => 1])
            ->orderBy('created_at', 'desc')
            ->offset($limit)
            ->limit($this->limit)
            ->get();
        if ($order_obj->isEmpty())
        {
            // 没有更多数据
            Log::info('User: ' . $id . ' 没有更多数据!');
            $result = $this->set_message(210, '没有更多数据');
            return $result;
        }
        $orders = $order_obj->toArray();

        $data['order_list'] = $orders;
        Log::info('User: ' . $id . ' 获取订单列表成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }
}
