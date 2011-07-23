{* @param Course $class *}
{* @param INTEGER $year *}

<div class="courseSub" data-id="{$class->ID}" data-dept="{$class->department}" data-num="{$class->number}">
    <span class="classDepartment">
        {$class->department}
    </span>
    <span class="classNumber">
        | {$class->number}
    </span>
    <span class="classTitle">
        | {$class->title}
    </span>
</div>