<?php

namespace App\Admin\Controllers;

use App\Admin\Model\User;
use App\Admin\Model\Task;
use App\Admin\Model\UserTask;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class UserTaskController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('公司任务')
            ->description('描述')
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User);

        $grid->model()->where('parent_id', 1001);
        $grid->id('ID')->sortable();
        $grid->userId('编号');
        $grid->name('名称');
        // $grid->avatar('Avatar');
        $grid->type('用户类型')->view('newStyle.user')->sortable();
        $grid->created_at('创建时间');
        $grid->updated_at('更新时间');
        $grid->actions(function ($actions)
        {
            // 去掉默认操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();

            // 添加操作
            $actions->append("<a href=\"/admin/task/allocateTask?user_id={$actions->getKey()}\"><i class=\"fa fa-paper-plane\"></i></a>");
        });
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();

        return $grid;
    }
}
