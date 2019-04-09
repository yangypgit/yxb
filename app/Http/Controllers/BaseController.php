<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Library\Ucloud\proxy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class BaseController extends Controller
{
    private $proxy;
    public $limit;

    public function __construct()
    {
        $this->proxy = new proxy();
        $this->limit = 20;
    }

    public function test(Request $request)
    {
        $this->recv_message('test()', $request->all());
    }

    // 加token认证
    public function attestation($token, $id)
    {
        $user = DB::table('yxb_users')
            ->where(['id' => $id])
            ->first();
        if (empty($user))
        {
            Log::error('User information does not exist! user_id: ' . $id);
            return false;
        }

        // 如果需要加超时验证也在这里
        if ($token != $user->token)
        {
            Log::error('User ' . $id . ' api 认证失败!');
            return false;
        }

        return $user;
    }

    public function hash2array($hash)
    {
        $array = [];
        $value = Redis::hgetall($hash);
        if ($value)
        {
            if (!array_key_exists(0, $value))
                return $value;
            $i = 0;
            for (;;)
            {
                if ($i >= count($value))
                    break;
                $array[$value[$i]] = $value[$i + 1];
                $i += 2;
            }
        }

        return $array;
    }

    public function recv_message($funcName, $message)
    {
        Log::info("\033[32mRecv message: " . $funcName . ' '
                     . json_encode($message) . "\033[0m");
    }

    public function set_message($status = 0, $msg = "fail", $return_msg = array())
    {
        $ret['status'] = $status;
        $ret['msg'] = $msg;
        if (!is_array($return_msg) || empty($return_msg))
        {
            $return_msg = (object)$return_msg;
        }
        $ret['data'] = $return_msg;
        Log::info("\033[31mSend message: " . json_encode($ret) . "\033[0m");

        return $ret;
    }

    public function get_id()
    {
        $id = Redis::rpop('yxb_luserid');

        return $id;
    }

    public function get_random_num()                                                                  
    {                                                                                                 
        $num = Redis::rpop('yxb_lrandom_num');                                                        
        Redis::lpush('yxb_lrandom_num', $num);                                                                                                                                                                
        return $num;                                                                                  
    }

    /***
     * 上传图片
     * @param $file
     * @return mixed
     */
    public function uploud_img($file,$filename)
    {
        //存储空间名
        $bucket = "miwan";
        //待上传文件的本地路径
        $file_   = $file->getRealPath();
        //该接口适用于0-10MB小文件,更大的文件建议使用分片上传接口
        list($data_, $err) = $this->proxy->UCloud_PutFile($bucket, $filename, $file_);
        return $err;
    }

}
