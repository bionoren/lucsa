{* @param Course $class *}
{* @param INTEGER $year *}

<div class="hovercard">
    <div id="{$class->getUID()}-overlay" class="bubble-content hovercard-inner" style="width: 300px;">
        <div id="{$class->getUID()}-overlay-header" class="bd">
            <span class="classDepartment">
                <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&school={$class->departmentlinkid}&cmd=courselist">{$class->department}</a>
            </span>
            <span class="classNumber">
                {$class->number}
            </span>
            <span class="classTitle">
                <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&course={$class->linkid}">{$class->title}</a>
                {if !$class->number}
                    <span class="note">
                        ({$class->hours} hour{if $class->hours != 1}s{/if})
                    </span>
                {/if}
                {if $class->offered < 3 || $class->years < 3}
                    <span class="note">
                        (
                        {if $class->offered < 3}
                            {if $class->offered == 1}Spring{else}Fall{/if}
                            {if $class->years < 3}
                                ,
                            {/if}
                        {/if}
                        {if $class->years < 3}
                            {if $class->years == 1}
                                Odd
                            {else}
                                Even
                            {/if}
                             years
                        {/if}
                        only)
                    </span>
                {/if}
                {if $class->noteID}
                    <span class="footnote">
                         {$class->noteID}
                    </span>
                {/if}
            </span>
        </div>
        <div class="completingCourseContainer{if !$class->isComplete()} hidden{/if}">
            Completed By:
            <br/>
            <a href="javascript:void(0)" class="ximage">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
            {if $class->isComplete()}
                {include file="course_sub.tpl" class=$class->completeClass}
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