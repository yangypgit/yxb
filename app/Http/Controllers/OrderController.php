<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    /*  获取订单列表  */
    public function get_order_list(Request $request)
    {
        $this->recv_message('get_order_list()', $request->all());
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

        // 分页查询
        if (!is_numeric($nowPage))                                                                                                                                                                            
        {                                                                                             
            $nowPage = 0;                                                                             
        }                                                                                             
        $limit = $this->limit * $nowPage;

        // 公司只看到有完整信息的订单 员工可以看到所有订单
        $flag = 1;
        if ($user->type == 1)
            $flag = 0;

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
            ->whereIn('flag', [$flag, 1])
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

    /*  提交订单  */
    public function sub_order_info(Request $request)
    {
        $this->recv_message('sub_order_info()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $token = $request->input('token');
        $task_id = $request->input('task_id');
        $number = $request->input('number');
        if (!is_numeric($id) || !is_string($token) || !is_numeric($task_id))
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

        // 验证用户是否有该任务
        $task = DB::table('yxb_user_task')
            ->where(['user_id' => $id, 'task_id' => $task_id])
            ->join('yxb_tasks', 'yxb_user_task.task_id', '=', 'yxb_tasks.id')
            ->select('yxb_user_task.unit_price', 'yxb_tasks.*')
            ->first();
        if (empty($task))
        {
            Log::error('Task information does not exist or the user does not have this task!');
            $result = $this->set_message(121, '任务信息不存在或该用户没有此任务！');
            return $result;
        }
        $task = (array)$task;

        $data['user_id'] = $id;
        $data['task_id'] = $task_id;
        $data['task_name'] = $task['name'];
        $data['unit_price'] = $task['unit_price'];
        // 获取任务信息来判断需要验证哪些参数
        if ($task['user_name'])
        {
            $name = $request->input('name');
            if (!is_string($name))
            {
                Log::error('The name cannot be empty!');
                $result = $this->set_message(122, '姓名不能为空！');
                return $result;
            }
            $data['name'] = $name;
        }

        if ($task['id_card'])
        {
            $id_card = $request->input('id_card');
            if(!preg_match("/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/", $id_card))
            {
                Log::error('The id_card cannot be empty!');
                $result = $this->set_message(123, '请填写正确的身份证号码！');
                return $result;
            }
            $data['id_card'] = $id_card;
        }

        if ($task['phone'])
        {
            $phone = $request->input('phone');
            if(!preg_match("/^1[34578]\d{9}$/", $phone))
            {
                Log::error('The phone cannot be empty!');
                $result = $this->set_message(124, '请填写正确的手机号码！');
                return $result;
            }
            $data['phone'] = $phone;
        }

        if ($task['picture'])
        {
            $img = 'http://www.rongcloud.cn/images/logo.png';
            $picture = $request->file('picture');
            if (!empty($picture))
            {
                $key1 = "yxb_" . time() . $picture->getClientOriginalName();
                $err = $this->uploud_img($picture ,$key1);
                if ($err)
                {
                    Log::error('Failed to upload picture!');
                    $result = $this->set_message(500, '上传截图失败！');
                    return $result;
                }
                $img = "http://miwan.ufile.ucloud.com.cn/" . $key1;
                $data['picture'] = $img;
            }
        }

        if (empty($number))
        {
            // 插入
            $data['number'] = 'YXB_' . date('YmdH', time()) . $this->get_random_num();
            $data['created_at'] = time();
            $data['updated_at'] = time();
            $num = DB::table('yxb_order_form')->insert($data);
            $mes = $data['number'] . ' Insert into';
        }
        else
        {
            // 更新
            $data['updated_at'] = time();
            $num = DB::table('yxb_order_form')->where('number', $number)->update($data);
            $mes = $number . ' Update';
        }

        if (!$num)
        {
            Log::error($mes . ' yxb_order_form table error!');
            $result = $this->set_message(500, '失败！');
            return $result;
        }

        Log::info('User: ' . $id . ' 提交用户信息成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /*  获取用户历史订单列表  */
    public function get_user_order_list(Request $request)
    {
        $this->recv_message('get_user_order_list()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $token = $request->input('token');
        $nowPage = $request->input('nowPage');
        if (!is_numeric($id) || !is_string($token) || !is_numeric($user_id))
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

        // 身份认证
        if ($user->type == 1)
        {
            Log::error('身份错误!' . ' User ' . $id . '是员工。');
            $result = $this->set_message(122, '身份错误！');
            return $result;
        }

        // 获取员工信息
        $staff = DB::table('yxb_users')
            ->where('id', $user_id)
            ->select('name', 'userId', 'money', 'no_settlement')
            ->first();
        $data['user_info'] = $staff;
        
        // 分页查询
        if (!is_numeric($nowPage))                                                                                                                                                                            
        {                                                                                             
            $nowPage = 0;                                                                             
        }                                                                                             
        $limit = $this->limit * $nowPage;

        $orders = [];
        $order_obj = DB::table('yxb_order_form')
            ->where(['user_id' => $user_id, 'flag' => 1])
            ->orderBy('created_at', 'desc')
            ->offset($limit)
            ->limit($this->limit)
            ->get();
        if (!$order_obj->isEmpty())
            $orders = $order_obj->toArray();

        $data['order_list'] = $orders;
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /* 订单筛选 (时间、银行、状态) */
    public function the_order_selection(Request $request)
    {
        $this->recv_message('the_order_selection()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $token = $request->input('token');
        $start_time = $request->input('start_time');
        $end_time = $request->input('end_time');
        $task_id = $request->input('task_id', 0);
        $status = $request->input('status');
        $state = $request->input('state');
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
        $limit = $this->limit * $nowPage;

        // 公司只看到有完整信息的订单 员工可以看到所有订单
        if ($user->type == 0)
            $where['flag'] = 1;
        if (!is_numeric($start_time))
            $start_time = 0;
        if (!is_numeric($end_time))
            $end_time = time();
        if (!empty($task_id) && is_numeric($task_id))
            $where['task_id'] = $task_id;
        if (is_numeric($status))
            $where['status'] = $status;
        if (is_numeric($state))
            $where['state'] = $state;
        if (!empty($user_id) && is_numeric($user_id))
            $where['user_id'] = $user_id;

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
            ->where($where)
            ->whereBetween('created_at', [$start_time, $end_time])
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
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /* 自审 */
    public function self_check(Request $request)
    {
        $this->recv_message('self_check()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $token = $request->input('token');
        $number = $request->input('number');
        if (!is_numeric($id) || !is_string($token) || !is_string($number))
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

        $name = $request->input('name');
        if (is_string($name))
        {
            $data['name'] = $name;
        }

        $id_card = $request->input('id_card');
        if(!empty($id_card) && !preg_match("/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/", $id_card))
        {
            $data['id_card'] = $id_card;
        }
        
        $phone = $request->input('phone');
        if(!empty($phone) && !preg_match("/^1[34578]\d{9}$/", $phone))
        {
            $data['phone'] = $phone;
        }

        $img = 'http://www.rongcloud.cn/images/logo.png';
        $picture = $request->file('picture');
        if (!empty($picture))
        {
            $key1 = "yxb_" . time() . $picture->getClientOriginalName();
            $err = $this->uploud_img($picture ,$key1);
            if ($err)
            {
                Log::error('Failed to upload picture!');
                $result = $this->set_message(500, '上传截图失败！');
                return $result;
            }
            $img = "http://miwan.ufile.ucloud.com.cn/" . $key1;
            $data['picture'] = $img;
        }

        $data['flag'] = 1;
        $data['updated_at'] = time();
        $num = DB::table('yxb_order_form')
            ->where('number', $number)
            ->update($data);
        if (!$num)
        {
            Log::error('self check error!');
            $result = $this->set_message(500, '自审失败！');
            return $result;
        }

        $result = $this->set_message(200, '成功');

        return $result;
    }

    /* 自审 */
    public function order_details(Request $request)
    {
        $this->recv_message('self_check()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $token = $request->input('token');
        $number = $request->input('number');
        if (!is_numeric($id) || !is_string($token) || !is_string($number))
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
        
        $order = DB::table('yxb_order_form')
            ->where('number', $number)
            ->first();
        if (empty($order))
        {
            Log::error('Failed to get order details!');
            $result = $this->set_message(500, '获取订单详情失败！');
            return $result;
        }

        $result = $this->set_message(200, '成功', $order);

        return $result;

    }

}
