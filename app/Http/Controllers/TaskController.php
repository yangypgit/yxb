<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Http\Model\YxbUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TaskController extends BaseController
{
    //
    public function __construct()
    {
    }

    /* 获取任务列表 */
    public function task_list(Request $request)
    {
        $this->recv_message('task_list()', $request->all());
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

        $tasks = [];
        $task_obj = DB::table('yxb_user_task')
            ->join('yxb_tasks', 'yxb_user_task.task_id', '=', 'yxb_tasks.id')
            ->where(['user_id' => $id, 'yxb_tasks.status' => 1, 'yxb_user_task.status' => 1])
            ->select('yxb_user_task.*', 'yxb_tasks.name')
            ->offset($limit)
            ->limit(10)
            ->get();
        if ($task_obj->isEmpty())
        {
            // 没有更多数据
            Log::info('User: ' . $id . ' 没有更多数据!');
            $result = $this->set_message(210, '没有更多数据');
            return $result;
        }
        $tasks = $task_obj->toArray();
        $data['task_list'] = $tasks;
        // 需要添加预计收入
        $data['income'] = $user->income;

        Log::info('User: ' . $id . ' 获取任务列表成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /* 获取任务详细信息 */
    public function task_info(Request $request)
    {
        $this->recv_message('task_info()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $task_id = $request->input('task_id');
        $token = $request->input('token');
        $nowPage = $request->input('nowPage');
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

        // 分页查询
        if (!is_numeric($nowPage))                                                                                                                                                                            
        {                                                                                             
            $nowPage = 0;                                                                             
        }                                                                                             
        $limit = 10 * $nowPage;

        $task = [];
        $task = DB::table('yxb_tasks')
            ->where('id', $task_id)
            ->first();
        if (!empty($task))
            $task = (array)$task;
        $data['task_info'] = $task;

        // 此任务相关订单
        $orders = [];
        $where['task_id'] = $task_id;
        // 公司只看到有完整信息的订单 员工可以看到所有订单
        if ($user->type == 0)
            $where['flag'] = 1;

        $userObj = DB::table('yxb_users')
            ->where(['id' => $id])
            ->orWhere('parent_id', $id)
            ->select('id');
        $order_obj = DB::table('yxb_order_form')
            ->where($where)
            ->joinSub($userObj, 'user_obj', function ($join) 
            {
                $join->on('yxb_order_form.user_id', '=', 'user_obj.id');
            })
            ->orderBy('created_at', 'desc')
            ->offset($limit)
            ->limit(10)
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

        Log::info('User: ' . $id . ' 获取任务详情成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /* 分配任务页 按人分配任务 */
    public function get_allocating_task(Request $request)
    {
        $this->recv_message('get_allocating_task()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $token = $request->input('token');
        $nowPage = $request->input('nowPage');
        if (!is_numeric($id) || !is_numeric($user_id) || !is_string($token))
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

        // 检查是否有分配任务权限（只有公司可以分配）
        if ($user->type != 0)
        {
            Log::error('No task permissions are assigned!');
            $result = $this->set_message(401, '没有分配任务权限！', 'No task permissions are assigned!');
            return $result;
        }

        // 拿到员工之前的任务
        $staff_task = [];
        $staff_task_obj = DB::table('yxb_user_task')
            ->where('user_id', $user_id)
            ->get();
        if (!$staff_task_obj->isEmpty())
            $staff_task = $staff_task_obj->toArray();
        $staff_task = array_column($staff_task, null, 'task_id');

        // 获取公司的任务
        $task_obj = DB::table('yxb_user_task')
            ->join('yxb_tasks', 'yxb_user_task.task_id', '=', 'yxb_tasks.id')
            ->where(['user_id' => $id, 'yxb_user_task.status' => 1])
            ->select('yxb_user_task.*', 'yxb_tasks.name')
            ->offset($limit)
            ->limit(10)
            ->get();
        if ($task_obj->isEmpty())
        {
            Log::info('User: ' . $id . '您当前没有任何任务!');
            $result = $this->set_message(112, '您当前没有任何任务！');
            return $result;
        }

        $tasks = $task_obj->toArray();
        foreach ($tasks as $key => $val)
        {
            if (array_key_exists($val->task_id, $staff_task))
            {
                $tasks[$key]->staff_unit_price = $staff_task[$val->task_id]->unit_price;
                $tasks[$key]->flag = $staff_task[$val->task_id]->status;
            }
            else
            {
                $tasks[$key]->staff_unit_price = 0;
                $tasks[$key]->flag = 0;
            }
        }
        /*
        $tasks = array_udiff($tasks, $staff_task, function ($a, $b)
        {
            $task_id_a = $a->task_id;
            $task_id_b = $b->task_id;

            if ($task_id_a == $task_id_b)
                return 0;
            else
                return -1;
        });
         */
        $tasks = array_values($tasks);
        $data['task_list'] = $tasks;

        Log::info('User: ' . $id . ' 获取分配任务成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /* 分配任务页 按任务分配给人 */
    public function get_allocating_task2(Request $request)
    {
        $this->recv_message('get_allocating_task2()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $task_id = $request->input('task_id');
        $token = $request->input('token');
        $nowPage = $request->input('nowPage');
        if (!is_numeric($id) || !is_numeric($task_id) || !is_string($token))
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

        // 检查是否有分配任务权限（只有公司可以分配）
        if ($user->type != 0)
        {
            Log::error('No task permissions are assigned!');
            $result = $this->set_message(401, '没有分配任务权限！', 'No task permissions are assigned!');
            return $result;
        }

        // 获取所有的员工
        $all_staff_obj = DB::table('yxb_users')
            ->where('parent_id', $id)
            ->select('id', 'userId', 'name')
            ->offset($limit)
            ->limit(10)
            ->get();
        if ($all_staff_obj->isEmpty())
        {
            // 没有员工
            Log::info('You don\'t have employees yet!');
            $result = $this->set_message(130, '您还没有员工！');
            return $result;
        }
        $all_staff = $all_staff_obj->toArray();

        // 获取所有有该任务的员工
        $staff_task = [];
        $staff_obj = DB::table('yxb_users')
            ->join('yxb_user_task', function ($join) use ($task_id)
            {
                $join->on('yxb_users.id', '=', 'yxb_user_task.user_id')
                    ->where('yxb_user_task.task_id', $task_id);
            })
            ->where('parent_id', $id)
            ->select('yxb_user_task.unit_price', 'yxb_user_task.finished', 'yxb_user_task.status', 'yxb_users.id')
            ->get();
        if (!$staff_obj->isEmpty())
            $staff_task = $staff_obj->toArray();
        $staff_task = array_column($staff_task, null, 'id');
        foreach ($all_staff as $key => $val)
        {
            if (array_key_exists($val->id, $staff_task))
            {
                $all_staff[$key]->unit_price = $staff_task[$val->id]->unit_price;
                $all_staff[$key]->finished = $staff_task[$val->id]->finished;
                $all_staff[$key]->flag = $staff_task[$val->id]->status;
            }
            else
            {
                $all_staff[$key]->unit_price = 0;
                $all_staff[$key]->finished = 0;
                $all_staff[$key]->flag = 0;
            }
        }
        $data['staff_list'] = $all_staff;

        /*
        // 差出所有没有改任务的员工
        $staff = array_udiff($all_staff, $staff_task, function ($a, $b)
        {
            $id_a = $a->id;
            $id_b = $b->id;

            if ($id_a == $id_b)
                return 0;
            else
                return -1;
        });
        $staff = array_values($staff);
        $data['staff'] = $staff;
         */
       
        Log::info('User: ' . $id . ' 获取分配任务成功！');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    /* 分配任务 按人分配任务 */
    public function allocating_task(Request $request)
    {
        $this->recv_message('allocating_task()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $token = $request->input('token');
        $task_id = $request->input('task_id');
        $unit_price = $request->input('unit_price');
        $status = $request->input('status');
        if (!is_numeric($id) || !is_numeric($user_id) || !is_string($token))
        {
            Log::error('id or user_id or token Parameter type error!');
            $result = $this->set_message(401, '参数类型错误！', 
                'id or user_id or token Parameter type error!');
            return $result;
        }

        if (empty($task_id) || empty($unit_price) || empty($status))
        {
            Log::error('task_id or unit_price or status is empty!');
            $result = $this->set_message(402, '参数不能为空！', 
                'task_id or unit_price or status is empty!');
            return $result;
        }

        // token认证
        $user = $this->attestation($token, $id);
        if (!$user)
        {
            $result = $this->set_message(666, 'api 认证失败！');
            return $result;
        }

        // 检查是否有分配任务权限（只有公司可以分配）
        if ($user->type != 0)
        {
            Log::error('No task permissions are assigned!');
            $result = $this->set_message(401, '没有分配任务权限！', 
                'No task permissions are assigned!');
            return $result;
        }

        // 拿到员工之前的任务
        $staff_task = DB::table('yxb_user_task')
            ->where('user_id', $user_id)
            ->pluck('task_id', 'task_id');
        if (!empty($staff_task))
            $staff_task = array_values((array)$staff_task)[0];

        // 组织数据
        $data['user_id'] = $user_id;
        $data['updated_at'] = time();

        $data_insert = [];
        $data_update = [];
        $task_id = explode(",", $task_id);
        $unit_price = explode(",", $unit_price);
        $status = explode(",", $status);
        foreach ($task_id as $key => $val)
        {
            if (array_key_exists($val, $staff_task))
            {
                // 员工有该任务 update
                $data['task_id'] = $val;
                $data['unit_price'] = $unit_price[$key];
                $data['status'] = $status[$key];
                $data_update[] = $data;
            }
            else
            {
                // 员工没有该任务 insert
                if ($status[$key] == 0)
                {
                    continue;
                }
                else
                {
                    // 新增任务
                    $data['task_id'] = $val;
                    $data['unit_price'] = $unit_price[$key];
                    $data['created_at'] = time();
                    $data['status'] = 1;
                    $data_insert[] = $data;
                }
            }
        }

        $ret = $this->insOrUpTask($data_insert, $data_update);
        if (!$ret)
        {
            $result = $this->set_message(500, '失败！');
            return $result;
        }

        Log::info('User: ' . $id . ' 给员工分配任务成功！');
        $result = $this->set_message(200, '成功');

        return $result;
    }

    /* 分配任务 按任务分配人 */
    public function allocating_task2(Request $request)
    {
        $this->recv_message('allocating_task2()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $task_id = $request->input('task_id');
        $token = $request->input('token');
        $user_id = $request->input('user_id');
        $unit_price = $request->input('unit_price');
        $status = $request->input('status');
        if (!is_numeric($id) || !is_numeric($task_id) || !is_string($token))
        {
            Log::error('id or task_id or token Parameter type error!');
            $result = $this->set_message(401, '参数类型错误！', 
                'id or task_id or token Parameter type error!');
            return $result;
        }

        if (empty($user_id) || empty($unit_price) || empty($status))
        {
            Log::error('user_id or unit_price or status is empty!');
            $result = $this->set_message(402, '参数不能为空！', 
                'user_id or unit_price or status is empty!');
            return $result;
        }

        // token认证
        $user = $this->attestation($token, $id);
        if (!$user)
        {
            $result = $this->set_message(666, 'api 认证失败！');
            return $result;
        }

        // 检查是否有分配任务权限（只有公司可以分配）
        if ($user->type != 0)
        {
            Log::error('No task permissions are assigned!');
            $result = $this->set_message(401, '没有分配任务权限！', 
                'No task permissions are assigned!');
            return $result;
        }

        // 获取所有有该任务的员工
        $staff_task = [];
        $staff_obj = DB::table('yxb_users')
            ->join('yxb_user_task', function ($join) use ($task_id)
            {
                $join->on('yxb_users.id', '=', 'yxb_user_task.user_id')
                    ->where('yxb_user_task.task_id', $task_id);
            })
            ->where('parent_id', $id)
            ->pluck('yxb_users.id', 'yxb_users.id');
        if (!$staff_obj->isEmpty())
            $staff_task = $staff_obj->toArray();

        // 组织数据
        $data['task_id'] = $task_id;
        $data['updated_at'] = time();

        $data_insert = [];
        $data_update = [];
        $user_id = explode(",", $user_id);
        $unit_price = explode(",", $unit_price);
        $status = explode(",", $status);
        foreach ($user_id as $key => $val)
        {
            if (array_key_exists($val, $staff_task))
            {
                // 员工有该任务 update
                $data['user_id'] = $val;
                $data['unit_price'] = $unit_price[$key];
                $data['status'] = $status[$key];
                $data_update[] = $data;
            }
            else
            {
                // 员工没有该任务 insert
                if ($status[$key] == 0)
                {
                    continue;
                }
                else
                {
                    // 新增任务
                    $data['user_id'] = $val;
                    $data['unit_price'] = $unit_price[$key];
                    $data['created_at'] = time();
                    $data['status'] = 1;
                    $data_insert[] = $data;
                }
            }
        }

        $ret = $this->insOrUpTask($data_insert, $data_update);
        if (!$ret)
        {
            $result = $this->set_message(500, '失败！');
            return $result;
        }

        Log::info('User: ' . $id . ' 给员工分配任务成功！');
        $result = $this->set_message(200, '成功');

        return $result;
    }

    /* 获取任务模板 */
    public function get_task_template(Request $request)
    {
        $this->recv_message('get_task_template()', $request->all());
        // 参数验证
        $id = $request->input('id');
        $token = $request->input('token');
        $task_id = $request->input('task_id');
        if (!is_numeric($id) || !is_numeric($task_id) || !is_string($token))
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

        $task = DB::table('yxb_tasks')
            ->where('id', $task_id)
            ->first();

        if (empty($task))
        {
            Log::error('get task info error!');
            $result = $this->set_message(500, '获取任务信息失败！');
            return $result;
        }

        Log::info('User: ' . $id . ' 获取任务信息成功！');
        $result = $this->set_message(200, '成功', $task);

        return $result;
    }

    /*  插入或更新用户任务  */
    public function insOrUpTask($data_insert, $data_update)
    {
        DB::beginTransaction();
        // 插入user_task 表
        if (!empty($data_insert))
        {
            $num = DB::table('yxb_user_task')->insert($data_insert);
            if (!$num)
            {
                DB::rollBack();
                Log::error('给员工分配任务失败!');
                return false;
            }
        }

        // 更新user_task 表
        if (!empty($data_update))
        {
            foreach ($data_update as $val)
            {
                $num = DB::table('yxb_user_task')
                    ->where(['user_id' => $val['user_id'], 'task_id' => $val['task_id']])
                    ->update($val);
                if (!$num)
                {
                    DB::rollBack();
                    Log::error('给员工更新任务失败!');
                    return false;
                }
            }
        }
        DB::commit();

        return true;
    }
}
