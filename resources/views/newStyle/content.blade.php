<p>

@if ($value == 0)
    <span class='label bg-yellow'>审核中</span>
@elseif ($value == 1)
    <span class='label bg-green'>通过</span>
@elseif ($value == 2)
    <span class='label bg-red'>未通过</span>
@endif

</p>
