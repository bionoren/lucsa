{* @param Course $class *}
{* @param INTEGER $year *}

<span class="classDepartment">
    <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&school={$class->getDepartmentLink()}&cmd=courselist">{$class->getDepartment()}</a>
</span>
<span class="classNumber">
    | {$class->getNumber()}
</span>
<span class="classTitle">
    | <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&course={$class->getLink()}">{$class->getTitle()}</a>
</span>