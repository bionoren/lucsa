{* Assumes an input variable $class of type Course *}
<span class="classDepartment">
    {$class->getDepartment()}
</span>
<span class="classNumber">
    | {$class->getNumber()}
</span>
<span class="classTitle">
    | {$class->getTitle()}
</span>