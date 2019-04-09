<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\BatchAction;

class AllocateTask extends BatchAction
{
    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {

    $.ajax({
        method: 'post',
        url: '/api/test',
        data: {
            _token:LA.token,
            ids: selectedRows(),
            action: {$this->action}
        },
        success: function () {
            $.pjax.reload('#pjax-container');
            toastr.success('操作成功');
        }
    });
});

EOT;
    }

    protected function render()
    {
        Admin::script($this->script());

        return "<a class='btn btn-xs fa fa-paper-plane grid-check-row' data-id='{$this->id}'></a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}
