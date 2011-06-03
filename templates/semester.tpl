{* @param Semester $semester *}

<span class="semesterTitle{if $semester->getCompletedHours() == $semester->getHours()} strike{/if}">
    {if $semester->getID() == 1}
        {Semester::$CARDINAL_STRINGS[($semester->getOrder()-1)/3]} {Semester::$SEMESTERS[$semester->getID()]} {$semester->getYear()}
    {else}
        {Semester::$CARDINAL_STRINGS[$semester->getOrder()-$semester->getOrder()/3]} Semester - {Semester::$SEMESTERS[$semester->getID()]} {$semester->getYear()}
    {/if}
</span>
<span class="semesterHours{if {$semester->getCompletedHours()} == {$semester->getHours()}} strike{/if}">
    {$semester->getHours() - $semester->getCompletedHours()} hours
</span>
<br/>
{foreach $semester->getClasses() as $class}
    {include file="course.tpl" class=$class}
{/foreach}