<?php

namespace App\Admin\Controllers;

use App\Admin\Model\Task;
use App\Admin\Model\UserTask;
use App\Library\Ucloud\proxy;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class TaskController extends Controller
{
    use HasResourceActions;

    private $proxy;

    public function __construct()
    {
        $this->proxy = new proxy();
    }

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('任务管理')
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
            ->header('新增任务')
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
        $grid = new Grid(new Task);

        $grid->id('ID')->sortable();
        $grid->name('任务名称');
        $grid->icon('任务图标')->image(55, 55);
        // $grid->avatar(trans('头像'))->lightbox(['width' => 100, 'height' => 100]);
        $grid->type('类型')->display(function ($type) 
        {
            return $type ? '链接' : '接口';
        });
        $grid->link('链接')->style('max-width:400px;word-break:break-all;');
        $grid->user_name('姓名')->display(function ($user_name) 
        {
            return $user_name ? '需要' : '不需要';
        });
        $grid->id_card('身份证')->display(function ($id_card) 
        {
            return $id_card ? '需要' : '不需要';
        });
        $grid->phone('电话')->display(function ($phone) 
        {
            return $phone ? '需要' : '不需要';
        });
        $grid->picture('截图')->display(function ($picture) 
        {
            return $picture ? '需要' : '不需要';
        });
        $grid->describe('描述')->style('max-width:200px;word-break:break-all;');
        $grid->status('任务状态')->view('newStyle.task')->sortable();
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
        $show = new Show(Task::findOrFail($id));

        $show->id('ID');
        $show->name('任务名称');
        $show->icon('任务图标')->image(55, 55);
        $show->type('类型')->as(function ($type)
        {
            return $type ? '链接' : '接口';
        })->label();
        $show->link('链接')->link();
        $show->user_name('姓名')->as(function ($user_name)
        {
            return $user_name ? '需要' : '不需要';
        })->label();
        $show->id_card('身份证')->as(function ($id_card)
        {
            return $id_card ? '需要' : '不需要';
        })->label();
        $show->phone('电话')->as(function ($phone)
        {
            return $phone ? '需要' : '不需要';
        })->label();
        $show->picture('截图')->as(function ($picture)
        {
            return $picture ? '需要' : '不需要';
        })->label();
        $show->describe('描述');
        $show->status('任务状态')->as(function ($status)
        {
            return $status ? '正常' : '下架';
        })->label('info');
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
        $form = new Form(new Task);

        $form->text('name', '任务名称')->rules('required');

        $proxy_temp = $this->proxy;
        $form->image('icon', '任务图标')
             ->name(function ($file) use ($proxy_temp) 
             { 
                 //存储空间名
                 $bucket = "sheep-game-image";
                 //待上传文件的本地路径
                 $filePath   = $file->getRealPath();
                 $fileName = time() . $file->getClientOriginalName();
                 //该接口适用于0-10MB小文件,更大的文件建议使用分片上传接口
                 list($data_, $err) = $proxy_temp->UCloud_PutFile($bucket, $fileName, $filePath);
                 $url = "http://sheep-game-image.cn-bj.ufileos.com/" . $fileName;

                 return $url;
             })->rules('required');
        $type = [0 => '链接', 1 => '接口'];
        $form->select('type', '类型')->options($type);
        $form->url('link', '链接');
        $user_name = [0 => '不需要', 1 => '需要'];
        $form->select('user_name', '姓名')->options($user_name);
        $id_card = [0 => '不需要', 1 => '需要'];
        $form->select('id_card', '身份证')->options($id_card);
        $phone = [0 => '不需要', 1 => '需要'];
        $form->select('phone', '电话')->options($phone);
        $picture = [0 => '不需要', 1 => '需要'];
        $form->select('picture', '截图')->options($picture);
        $form->textarea('describe', '描述');
        $status = [0 => '下架', 1 => '正常'];
        $form->radio('status', '任务状态')
             ->options($status)
             ->default(1);
        $form->currency('userTask.unit_price', '单价')->symbol('￥');

        $form->saved(function (Form $form)
        {
            // 创建wcb的任务
            $task = UserTask::where(['user_id' => 1001, 
                'task_id' => $form->model()->id])->first();
            if ($task)
            {
                UserTask::where(['user_id' => 1001,
                'task_id' => $form->model()->id])->update(['unit_price' => 1]);
            }
            else
            {
                $data['user_id'] = 1001;
                $data['task_id'] = $form->model()->id;
                $data['unit_price'] = 1;
                $data['created_at'] = time();
                $data['updated_at'] = time();
                $num = UserTask::insert($data);
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

}
