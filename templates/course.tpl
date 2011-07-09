{* @param Course $class *}
{* @param INTEGER $year *}

<div id="{$class->getUID()}" class="classOverlay {if empty($smarty.get.disp) || $smarty.get.disp == "summary"}move{else}noMove{/if} {if $class->isComplete()}strike{else}nostrike{/if}" data-id="{$class->getID()}" data-hours="{$class->getHours()}">
    {include file="course_overlay.tpl" class=$class year=$year}
    <div class="summary">
        <span class="classDepartment">
            {$class->getDepartment()}
        </span>
        <span class="classNumber">
            | {$class->getNumber()}
        </span>
        <span class="classTitle">
            |
            {$class->getTitle()}
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
</div>