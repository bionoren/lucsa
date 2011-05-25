{* @param Course $class *}

<span id="{$class->getUID()}" class="classOverlay {if $class->isComplete()}strike{else}nostrike{/if}">
    <div id="{$class->getUID()}complete" class="overlay{if !{$class->isComplete()}} hidden{/if}">
        Completed By:
        <br/>
        <a href="javascript:void(0)" class="ximage">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
        {if {$class->getCompleteClass()}}
            {include file="course_sub.tpl" class=$class->getCompleteClass()}
        {/if}
    </div>
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
</span>