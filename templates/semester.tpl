{* @param Semester $semester *}

<span class="semesterTitle{if $semester->getCompletedHours() == $semester->getHours()} strike{/if}">
    {Semester::$CARDINAL_STRINGS[$order-1]} Semester - {Semester::$SEMESTERS[$semester->getID()]} {$semester->getYear()}
</span>
<span class="semesterHours{if {$semester->getCompletedHours()} == {$semester->getHours()}} strike{/if}">
    {$semester->getHours() - $semester->getCompletedHours()} hours
</span>
<br/>
{foreach $semester->getClasses() as $class}
    {include file="course.tpl" class=$class}
{/foreach}