<?php

namespace App\Admin\Controllers;

use App\Admin\Model\User;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Redis;
use Encore\Admin\Widgets\Table;

class UserController extends Controller
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
            ->header('用户信息')
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
            ->header('编辑')
            ->description('描述')
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
            ->header('创建新用户')
            ->description('描述')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User);

        $grid->model()->where('type', '=', 0);
        $grid->actions(function ($actions) 
        {
            if ($actions->getKey() == 1001)
            {
                $actions->disableDelete();
            }
        });
        $grid->id('ID')->expand(function ($model)
        {
            $staffs = $model->where('parent_id', $model->id)->take(20)->get()->map(function ($staff)
            {
                return $staff->only(['id', 'userId', 'userName', 'name', 'contact', 'created_at', 'updated_at']);
            });

            return new Table(['ID', '编号', '用户名', '名称', '联系方式', '创建时间', '更新时间'], $staffs->toArray());
        })->sortable();
        $grid->userId('编号');
        $grid->userName('用户名');
        $grid->name('名称');
        // $grid->avatar('Avatar');
        $grid->type('用户类型')->view('newStyle.user')->sortable();
        $grid->contact('联系方式');
        $grid->money('已结算金额');
        $grid->no_settlement('未结算金额');
        $grid->income('本月收入');
        $grid->register('员工人数');
        $grid->created_at('创建时间');
        $grid->updated_at('更新时间');

        // 筛选按钮
        // $grid->disableFilter();
        // 第一列选择按钮
        $grid->disableRowSelector();
        $grid->disableExport();
        // 查 改 删
        // $grid->disableActions();

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
        $show = new Show(User::findOrFail($id));

        $show->id('Id');
        $show->userId('编号');
        $show->userName('用户名');
        $show->password('密码');
        $show->name('名称');
        // $show->avatar('Avatar');
        $show->type('用户类型')->as(function ($type)
        {
            return $type ? '员工' : '公司';
        });
        $show->contact('联系方式');
        $show->money('已结算金额');
        $show->no_settlement('未结算金额');
        $show->income('本月收入');
        $show->register('员工人数');
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
        $form = new Form(new User);

        if (request()->isMethod('POST'))
        {
            $form->number('id', 'ID');
            $form->number('parent_id', 'PID');
            $form->text('userId', 'UID');
            $form->submitted(function (Form $form) 
            {
                $id = Redis::rpop('yxb_luserid');
                $form->id = (int)$id;
                $form->parent_id = 1001;
                $form->userId = $this->get_userId('BS1001');
            });
        }

        $form->text('userName', '用户名')->rules(function ($form) 
        {
            // 如果不是编辑状态，则添加字段唯一验证
            if (!$id = $form->model()->id)
            {
                return 'unique:yxb_users,userName';
            }
        });
        $form->password('password', trans('密码'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('确认密码'))->rules('required')
             ->default(function ($form) 
             {
                 return $form->model()->password;
             });

        $form->ignore(['password_confirmation']);
        $form->text('name', '昵称');
        // $form->image('avatar', '头像')->default('users/default.png');
        $type = [0 => '公司', 1 => '员工'];
        $form->select('type', '用户类型')->options($type);
        $form->text('contact', '联系方式');

        $form->saving(function (Form $form) 
        {
            if ($form->password && $form->model()->password != $form->password) 
            {
                $form->password = bcrypt($form->password);
            }
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

        // 更新拥有员工人数
        $user = new User;
        $user->incrementByUserId('register', $userId);

        return $newUserId;
    }

    public function del_user($userId)
    {
        $ret = Redis::hexists('yxb_hregister_index', $userId);
        if ($ret)
        {
            // 存在 更新
            Redis::hincrby('yxb_hregister_index', $userId, -1);
            // 更新拥有员工人数
            $user = new User;
            $user->decrementByUserId('register', $userId);
        }
    }
}
