<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class AllocateTask
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

        // Your code.
        var_dump('hello'); exit;
        console.log($(this).data('id'));
});

SCRIPT;
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
