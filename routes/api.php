<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'EnableCrossRequestMiddleware'], function()
{
    Route::get('test', function () {
        return "hello";
    });

    /*  test  */
    Route::post('test', 'BaseController@test');
    /*  END  */

    Route::post('login', 'LoginController@login');

    Route::post('register', 'LoginController@register');

    Route::post('taskList', 'TaskController@task_list');
    
    Route::post('taskInfo', 'TaskController@task_info');

    Route::post('getAllocatingTask', 'TaskController@get_allocating_task');

    Route::post('getAllocatingTask2', 'TaskController@get_allocating_task2');

    Route::post('allocatingTask', 'TaskController@allocating_task');

    Route::post('allocatingTask2', 'TaskController@allocating_task2');

    Route::post('getTaskTemplate', 'TaskController@get_task_template');

    Route::post('getUserInfo', 'UsersController@user_info');

    Route::post('getStaffInfo', 'UsersController@get_staff_info');

    Route::post('earnings', 'UsersController@earnings');

    Route::post('getOrderList', 'OrderController@get_order_list');

    Route::post('subOrder', 'OrderController@sub_order_info');

    Route::post('getUserOrder', 'OrderController@get_user_order_list');

    Route::post('selectOrder', 'OrderController@the_order_selection');

    Route::post('selfCheck', 'OrderController@self_check');

    Route::post('orderDetails', 'OrderController@order_details');
});
