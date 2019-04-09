<?php

namespace App\Admin\Controllers;

use App\Admin\Model\User;
use App\Admin\Model\Task;
use App\Admin\Model\UserTask;
use App\Admin\Extensions\Tools\AllocateTask;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Request;

class AllocateTaskController extends Controller
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
        $user_id = request()->input('user_id');
        return $content
            ->header('分配任务')
            ->body($this->grid($user_id))
            ->body($this->user_no_task($user_id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑')
            ->body($this->form()->edit($id));
    }

    // 公司已有任务列表
    protected function grid($id)
    {
        $grid = new Grid(new UserTask);

        $grid->header(function ($query)
        {
            return "<h4>已有任务</h4>";
        });
        $grid->model()->where('user_id', $id)->where('status', 1);
        $grid->id('ID')->sortable();
        // $grid->user_id('用户ID')->sortable();
        $grid->task_id('任务')->display(function ($task_id)
        {
            try
            {
                $task = Task::find($task_id)->name;
            } catch (\Exception $e) {
                $task = '任务已删除';
            }
            return $task;
        });
        $grid->unit_price('单价');
        $grid->finished('成功单数');
        $grid->fail('未通过单数')->badge();
        $grid->status('任务状态')->view('newStyle.task2')->sortable();
        $grid->created_at('创建时间')->display(function ($created_at)
        {
            return date('Y-m-d H:i:s', $created_at);
        });
        $grid->updated_at('更新时间')->display(function ($updated_at)
        {
            return date('Y-m-d H:i:s', $updated_at);
        });

        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();
        $grid->disableTools();
        $grid->actions(function ($actions) use ($id)
        {
            // 去掉默认操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();

            // 添加操作
            // 编辑
            $actions->append("<a href=\"/admin/task/allocateTask/{$actions->getKey()}/edit\"><i class=\"fa fa-edit\"></i></a>");
            // 取消任务
            $actions->append("<a href=\"/admin/task/delTask?user_id={$id}&task_id={$actions->row->task_id}\"><i class=\"fa fa-arrow-down\"></i></a>");
        });

        return $grid;
    }

    // 公司未有任务列表
    protected function user_no_task($id)
    {
        $grid = new Grid(new UserTask);

        $grid->header(function ($query)
        {
            return "<h4>没有任务</h4>";
        });
        $staff_task = UserTask::where('user_id', $id)->where('status', 1)->pluck('task_id');
        $wcb_task = UserTask::where('user_id', 1001)->pluck('task_id');
        $task = array_diff($wcb_task->toArray(), $staff_task->toArray());
        $grid->model()->where('user_id', 1001)->where('status', 1)->whereIn('task_id', $task);
        $grid->id('ID')->sortable();
        // $grid->user_id('用户ID')->sortable();
        $grid->task_id('任务')->display(function ($task_id)
        {
            try
            {
                $task = Task::find($task_id)->name;
            } catch (\Exception $e) {
                $task = '任务已删除';
            }
            return $task;
        });
        $grid->unit_price('单价');
        $grid->finished('成功单数');
        $grid->fail('未通过单数')->badge();
        $grid->status('任务状态')->view('newStyle.task2')->sortable();
        $grid->created_at('创建时间')->display(function ($created_at)
        {
            return date('Y-m-d H:i:s', $created_at);
        });
        $grid->updated_at('更新时间')->display(function ($updated_at)
        {
            return date('Y-m-d H:i:s', $updated_at);
        });

        /*
        $grid->tools(function ($tools)
        {
            $tools->disableRefreshButton();
            $tools->batch(function ($batch)
            {
                $batch->disableDelete();
                $batch->add('分配任务', new AllocateTask(0));
            });
        });
         */
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();
        $grid->disableTools();
        $grid->actions(function ($actions) use ($id)
        {
            // 去掉默认操作
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();

            // 添加操作
            // $actions->append("<a href=\"/admin/task/allocateTask/{$actions->getKey()}/edit\"><i class=\"fa fa-edit\"></i></a>");
            // 分配任务
            $actions->append("<a href=\"/admin/task/addTask?user_id={$id}&task_id={$actions->row->task_id}\"><i class=\"fa fa-arrow-up\"></i></a>");
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserTask);

        $form->currency('unit_price', '单价')
             ->default(0.00)->symbol('￥');
        $status = [0 => '失效', 1 => '有效'];
        $form->radio('status', '任务状态')
             ->options($status)
             ->default(1);

        $form->tools(function (Form\Tools $tools) 
        {
            $tools->disableList();
            $tools->disableDelete();
            $tools->disableView();

            // 添加自定义按钮
            // $tools->add("<a href=\"/admin/task/allocateTask?user_id={}\" class=\"btn btn-sm btn-default\"><i class=\"fa fa-list\"></i>&nbsp;&nbsp;返回</a>");
        });
        // 去掉form脚部的元素
        $form->footer(function ($footer) 
        {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }

    public function add_task()
    {
        $user_id = request()->input('user_id');
        $task_id = request()->input('task_id');
        // 查看用户是否有该任务 有更新为有效 没有 添加
        $task = UserTask::where(['user_id' => $user_id, 
            'task_id' => $task_id])->first();
        if ($task)
        {
            UserTask::where(['user_id' => $user_id,
            'task_id' => $task_id])->update(['status' => 1]);
        }
        else
        {
            $data['user_id'] = $user_id;
            $data['task_id'] = $task_id;
            $data['created_at'] = time();
            $data['updated_at'] = time();
            UserTask::insert($data);
        }

        return redirect("/admin/task/allocateTask?user_id={$user_id}");
    }

    public function del_task()
    {
        $user_id = request()->input('user_id');
        $task_id = request()->input('task_id');

        UserTask::where(['user_id' => $user_id,
        'task_id' => $task_id])->update(['status' => 0]);

        return redirect("/admin/task/allocateTask?user_id={$user_id}");
    }

}
