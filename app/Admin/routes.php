<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->resource('users', UserController::class);

    $router->resource('task/tasks', TaskController::class);

    $router->resource('task/userTask', UserTaskController::class);

    $router->resource('task/allocateTask', AllocateTaskController::class);
    $router->get('task/addTask', 'AllocateTaskController@add_task');
    $router->get('task/delTask', 'AllocateTaskController@del_task');

    $router->resource('check/list', OrderFormController::class);

    $router->get('check/check', 'OrderFormController@check');

});
