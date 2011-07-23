{* @param Course $class *}
{* @param INTEGER $year *}

<div id="{$class->getUID()}" class="classOverlay {if empty($smarty.get.disp) || $smarty.get.disp == "summary"}move{else}noMove{/if} {if $class->isComplete()}strike{else}nostrike{/if}" data-id="{$class->ID}" data-hours="{$class->hours}">
    {include file="course_overlay.tpl" class=$class year=$year}
    <div class="classSummary">
        <span class="classDepartment">
            {$class->department}
        </span>
        <span class="classNumber">
            | {$class->number}
        </span>
        <span class="classTitle">
            |
            {$class->title}
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
</div>