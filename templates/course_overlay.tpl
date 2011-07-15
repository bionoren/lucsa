{* @param Course $class *}
{* @param INTEGER $year *}

<div class="hovercard">
    <div id="{$class->getUID()}-overlay" class="bubble-content hovercard-inner" style="width: 300px;">
        <div id="{$class->getUID()}-overlay-header" class="bd">
            <span class="classDepartment">
                <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&school={$class->getDepartmentLink()}&cmd=courselist">{$class->getDepartment()}</a>
            </span>
            <span class="classNumber">
                {$class->getNumber()}
            </span>
            <span class="classTitle">
                <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&course={$class->getLink()}">{$class->getTitle()}</a>
                {if !$class->getNumber()}
                    <span class="note">
                        ({$class->getHours()} hour{if $class->getHours() != 1}s{/if})
                    </span>
                {/if}
                {if $class->getOffered() < 3 || $class->getYears() < 3}
                    <span class="note">
                        (
                        {if $class->getOffered() < 3}
                            {if $class->getOffered() == 1}Spring{else}Fall{/if}
                            {if $class->getYears() < 3}
                                ,
                            {/if}
                        {/if}
                        {if $class->getYears() < 3}
                            {if $class->getYears() == 1}
                                Odd
                            {else}
                                Even
                            {/if}
                             years
                        {/if}
                        only)
                    </span>
                {/if}
                {if $class->getNoteID()}
                    <span class="footnote">
                         {$class->getNoteID()}
                    </span>
                {/if}
            </span>
        </div>
        <div class="completingCourseContainer hidden">
            Completed By:
            <br/>
            <a href="javascript:void(0)" class="ximage">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
            {if $class->isComplete()}
                {include file="course_sub.tpl" class=$class->getCompleteClass()}
            {/if}
        </div>
        <div class="hovercard-inner-footer">
            {if $class->getPrerequisites()}
                {$class->getPrerequisites()}
            {/if}
            <br>
            {if $class->getCorequisites()}
                {$class->getCorequisites()}
            {/if}
            <br>
            {if $class->getPreOrCorequisites()}
                {$class->getPreOrCorequisites()}
            {/if}
        </div>
    </div>
    <div class="bubble-divot bubble-divot-top"></div>
</div>