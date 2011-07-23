{* @param Semester $semester *}

<span class="semesterTitle{if $semester->completedHours == $semester->hours} strike{/if}">
    {if $semester->semesterID == 1}
        {Semester::$CARDINAL_STRINGS[($semester->order-1)/3]} {Semester::$SEMESTERS[$semester->semesterID]} {$semester->year}
    {else}
        {Semester::$CARDINAL_STRINGS[$semester->order-$semester->order/3]} Semester - {Semester::$SEMESTERS[$semester->semesterID]} {$semester->year}
    {/if}
</span>
<span class="semesterHours{if {$semester->completedHours} == {$semester->hours}} strike{/if}">
    {$semester->hours - $semester->completedHours} hours
</span>
<br/>
{foreach $semester->classes as $class}
    {include file="course.tpl" class=$class}
{/foreach}