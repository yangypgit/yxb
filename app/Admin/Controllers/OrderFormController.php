<?php

namespace App\Admin\Controllers;

use App\Admin\Model\User;
use App\Admin\Model\UserTask;
use App\Admin\Model\OrderForm;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OrderFormController extends Controller
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
            ->header('订单详情')
            ->description('描述')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('详细信息')
            ->description('描述')
            ->body($this->detail($id));
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
            ->header('审核')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('描述')
            ->body($this->form());
    }

    /**
     * Check interface.
     *
     * @param Content $content
     * @return Content
     */
    public function check(Content $content)
    {
        $content->header('订单审核');
        $orderForm = new OrderForm;
        $id = $orderForm->where('status', 0)->pluck('id');
        foreach ($id as $val)
        {
            $content->body($this->checkForm());
            // $content->body($this->form()->edit($val));
        }
        return $content;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderForm);

        // $grid->model()->orderBy('status');
        $grid->id('ID')->sortable();
        $grid->number('订单编号')/*->setAttributes(['style' => 'color:red;'])*/;
        $grid->user_id('用户ID');
        $grid->column('用户')->display(function ()
        {
            try
            {
                $user = User::find($this->user_id)->name;
            } catch (\Exception $e) {
                $user = '用户已删除';
            }
            return $user;
        });
        $grid->task_name('任务名称');
        $grid->unit_price('单价');
        $grid->name('姓名');
        $grid->id_card('身份证');
        $grid->phone('电话');
        $grid->picture('截图')->image(100, 100);
        // 第一种方式
        $grid->status('审核状态')->view('newStyle.content')->sortable();
        // 第二种方式
        $grid->state('订单状态')->display(function ($state)
        {
            if ($this->status == 1)
            {
                if ($state == 0)
                    return "<span class='label bg-yellow'>公司审核中</span>";
                elseif ($state == 1) 
                    return "<span class='label bg-green'>公司已结算</span>";
                elseif ($state == 2) 
                    return "<span class='label bg-red'>公司废弃</span>";
            }
            else
            {
                return "<span class='label bg-blue'>无</span>";
            }
        });
        $grid->created_at('创建时间')->display(function ($created_at)
        {
            return date('Y-m-d H:i:s', $created_at);
        });
        $grid->updated_at('更新时间')->display(function ($updated_at)
        {
            return date('Y-m-d H:i:s', $updated_at);
        });

        $grid->filter(function ($filter)
        {
            // $filter->where('user_id', '用户ID');
            // $filter->between('created_at', '创建时间')->datetime();
        });
        $grid->disableRowSelector();
        $grid->disableExport();
        // $grid->disableFilter();
        $grid->disableCreateButton();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(OrderForm::findOrFail($id));

        $show->id('ID');
        $show->number('订单编号');
        $show->user_id('用户ID');
        $show->column('用户')->as(function ()
        {
            try
            {
                $user = User::find($this->user_id)->name;
            } catch (\Exception $e) {
                $user = '用户已删除';
            }
            return $user;
        });

        $show->task_id('任务ID');
        $show->task_name('任务名称');
        $show->unit_price('单价');
        $show->name('姓名');
        $show->id_card('身份证');
        $show->phone('电话');
        $show->picture('截图')->image();
        $show->status('审核状态')->as(function ($status)
        {
            if ($status == 0)
                return '审核中';
            elseif ($status == 1) 
                return '通过';
            elseif ($status == 2) 
                return '未通过';
        })->label();
        $show->state('订单状态')->as(function ($state)
        {
            if ($this->status == 1)
            {
                if ($state == 0)
                    return '公司审核中';
                elseif ($state == 1) 
                    return '公司已结算';
                elseif ($state == 2) 
                    return '公司废弃';
            }
            else
            {
                return '无';
            }
        })->label('primary');
        $show->created_at('创建时间');
        $show->updated_at('更新时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderForm);

        $form->display('number', '订单编号');
        $form->display('user_id', '用户ID');
        $form->display('task_id', '任务ID');
        $form->display('task_name', '任务名称');
        $form->display('unit_price', '单价')->default(0.00);
        $form->display('name', '姓名');
        $form->display('id_card', '身份证');
        $form->display('phone', '电话');
        $form->image('picture', '截图');
        $status = [0 => '审核中', 1 => '通过', 2 => '未通过'];
        $form->radio('status', '审核状态')->options($status);
        // $state = [0 => '公司审核中', 1 => '公司已结算', 2 => '公司废弃'];
        // $form->select('state', '订单状态')->options($state);

        $form->tools(function (Form\Tools $tools)
        {
            $tools->disableDelete();
        });
        // 去掉form脚部的元素
        $form->footer(function ($footer) 
        {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->disableReset();

        // 保存后回调
        $form->saved(function (Form $form)
        {
            if (1 == $form->model()->status)
            {
                // 审核通过
                // 判断是否是重复提交
                $order = OrderForm::where('number', $form->model()->number)->first();
                if ($order->status == 0)
                {
                    // 获取所有用户的ID 员工、公司、wcb
                    $user = User::where('id', $form->model()->user_id)->first();
                    $num = UserTask::whereIn('user_id', [$form->model()->user_id, $user->parent_id, 1001])
                        ->increment('finished');
                }
            }
            elseif (2 == $form->model()->status)
            {
                // 未通过
                $order = OrderForm::where('number', $form->model()->number)->first();
                if ($order->status == 0)
                {
                    $user = User::where('id', $form->model()->user_id)->first();
                    $num = UserTask::whereIn('user_id', [$form->model()->user_id, $user->parent_id, 1001])
                        ->increment('fail');
                }
            }
        });

        return $form;
    }

    /**
     * Make a form builder for check.
     *
     * @return Form
     */
    protected function checkForm()
    {
        $form = new Form(new OrderForm);

        $form->display('number', '订单编号');
        $form->display('user_id', '用户ID');
        $form->display('task_id', '任务ID');
        $form->display('task_name', '任务名称');
        $form->display('unit_price', '单价')->default(0.00);
        $form->display('name', '姓名');
        $form->display('id_card', '身份证');
        $form->display('phone', '电话');
        $form->image('picture', '截图');
        $status = [0 => '审核中', 1 => '通过', 2 => '未通过'];
        $form->radio('status', '审核状态')->options($status);

        $form->tools(function (Form\Tools $tools)
        {
            $tools->disableList();
            $tools->disableDelete();
        });
        // 去掉form脚部的元素
        $form->footer(function ($footer) 
        {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->disableReset();

        return $form;
    }
}
