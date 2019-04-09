<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Http\Model\YxbUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class LoginController extends BaseController
{
    /* 登录 */
    public function login(Request $request)
    {
        // $this->recv_message('login()', $request->all());
        $username = $request->input('username');
        if (empty($username))
        {
            Log::error('username is empty!');
            $result = $this->set_message(101, '用户名不能为空！', 'username is empty!');
            return $result;
        }

        $password = $request->input('password');
        if (empty($password))
        {
            Log::error('password is empty!');
            $result = $this->set_message(102, '密码不能为空！', 'password is empty!');
            return $result;
        }

        $user = DB::table('yxb_users')
            ->where(['username' => $username])
            ->first();
        if (empty($user))
        {
            Log::error('User information does not exist! username: ' . $username);
            $result = $this->set_message(405, '用户信息不存在！', 'User information does not exist!');
            return $result;
        }

        if (!password_verify($password, $user->password))
        {
            Log::error('Password error! username: ' . $username);
            $result = $this->set_message(106, '密码错误！', 'Password error!');
            return $result;
        }

        $user->token = $this->set_token($user->id);

        $data['user_info'] = $user;
        log::info('user: ' . $username . ' login ok!');
        $result = $this->set_message(200, '成功', $data);

        return $result;
    }

    // 注册用户
    public function register(Request $request)
    {
        // $this->recv_message('register()', $request->all());
        $id = $request->input('id');
        $name = $request->input('name');
        $contact = $request->input('contact');
        if (!is_numeric($id) || !is_string($name))
        {
            Log::error('Parameter type error!');
            $result = $this->set_message(401, '参数类型错误！', 'Parameter type error!');
            return $result;
        }

        $username = $request->input('username');
        if (empty($username))
        {
            Log::error('username is empty!');
            $result = $this->set_message(101, '用户名不能为空！', 'username is empty!');
            return $result;
        }

        $password = $request->input('password');
        if (empty($password))
        {
            Log::error('password is empty!');
            $result = $this->set_message(102, '密码不能为空！', 'password is empty!');
            return $result;
        }

        if(!preg_match("/^1[34578]\d{9}$/", $contact))
        {
            $contact = '17608888888';
            /*
            Log::error('contact is error!');
            $result = $this->set_message(102, '请输入正确的手机号码！', 'contact is error!');
            return $result;
             */
        }

        $user = YxbUser::findUserById($id);
        if (empty($user))
        {
            Log::error('User information does not exist! username: ' . $id);
            $result = $this->set_message(405, '用户信息不存在！', 'contact is error!');
            return $result;
        }

        // 用户名不能重复
        $new_user = YxbUser::findUserByUserName($username);
        if ($new_user)
        {
            Log::error('The user name already exists');
            $result = $this->set_message(103, '用户名已存在！', 'The user name already exists!');
            return $result;
        }

        // 图片处理
        $img = 'http://www.rongcloud.cn/images/logo.png';
        $avatar = $request->file('avatar');
        if (!empty($avatar))
        {
            $key1 = "yxb_" . time() . $avatar->getClientOriginalName();
            $err = $this->uploud_img($avatar ,$key1);
            if ($err)
            {
                Log::error('Failed to upload avatar!');
                $result = $this->set_message(500, '上传头像失败！', 'Failed to upload avatar!');
                return $result;
            }
            $img = "http://miwan.ufile.ucloud.com.cn/" . $key1;
            $data['avatar'] = $img;
        }

        $data['id'] = $this->get_id();
        $data['parent_id'] = $id;
        $data['userId'] = $this->get_userId($user->userId);
        $data['userName'] = $username;
        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        $data['name'] = $name;
        $data['type'] = 1;
        $data['contact'] = $contact;
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $data['updated_at'] = date('Y-m-d H:i:s', time());

        $num = DB::table('yxb_users')->insert($data);
        if (!$num)
        {
            Log::error('Failed to add new user!');
            $result = $this->set_message(500, '添加新用户失败！', 'Failed to add new user!');
            return $result;
        }
        $data_['user_info'] = $data;
        log::info('user: ' . $user->name . ' add new user ok!');
        $result = $this->set_message(200, '成功', $data_);

        return $result;
    }

    public function get_userId($userId)
    {
        $ret = Redis::hexists('yxb_hregister_index', $userId);
        if ($ret)
        {
            // 存在 更新
            Redis::hincrby('yxb_hregister_index', $userId, 1);
            $newUserId = $userId . sprintf("%'.04d", Redis::hget('yxb_hregister_index', $userId));
        }
        else
        {
            // 不存在 插入
            Redis::hset('yxb_hregister_index', $userId, 1);
            $newUserId = $userId . '0001';
        }

        return $newUserId;
    }

    public function set_token($id)
    {
        // 生成token
        srand((double)microtime() * 1000000);
        $appSecret = 'YxBProject'; // 自己随便写的
        $nonce = rand(); // 获取随机数。
        $timestamp = time() * 1000; // 获取时间戳（毫秒）。

        $signature = md5(sha1($appSecret . $nonce . $timestamp));

        // 更新用户token
        $num = DB::table('yxb_users')
            ->where(['id' => $id])
            ->update(['token' => $signature]);
        if (!$num)
        {
            return false;
        }

        return $signature;
    }
}
