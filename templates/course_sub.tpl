{* @param Course $class *}
{* @param INTEGER $year *}

<div class="courseSub" data-id="{$class->getID()}" data-dept="{$class->getDepartment()}" data-num="{$class->getNumber()}">
    <span class="classDepartment">
        {$class->getDepartment()}
    </span>
    <span class="classNumber">
        | {$class->getNumber()}
    </span>
    <span class="classTitle">
        | {$class->getTitle()}
    </span>
</div>