{* @param CourseSequence $cs *}
{* @param INTEGER $year *}

<table id="{$cs->getID()}">
    <tr>
        <td colspan="2" class="majorTitle">
            <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&degree={$cs->getLink()}">{$cs->getName()} ({$cs->getAcronym()})</a>
            <br/>
            <span class="sequenceTitle">
                Sequence Sheet for {$year}-{$year+1}
            </span>
            <br/>
            <span class="sequenceLinks">
                <a href="?disp=summary">Summary View</a>
            </span>
            &nbsp;
            <span class="sequenceLinks">
                <a href="?disp=list">Requirements List View</a>
            </span>
            <br style="vertical-align:top; line-height:28px;"/>
        </td>
    </tr>
    {block name=classInfo}{/block}
    <tr>
        <td colspan="2" style="text-align:center;">
            Completed Hours: <span id="{$cs->getID()}-completedHours">{$cs->getCompletedHours()}</span>
            <br/>
            Remaining Hours: <span id="{$cs->getID()}-remainingHours">{$cs->getHours()-$cs->getCompletedHours()}</span>
            <br/>
            Total Hours: {$cs->getHours()}
        </td>
    </tr>
    {if $cs->getNotes()->count() > 0}
        <tr>
            <td colspan="3" class="endNote">
                Notes:
            {foreach $cs->getNotes()->getNotes() as $i=>$note}
                <br/>
                <span class="endNote">{$i}</span>: {$note}
            {/foreach}
            </td>
        </tr>
    {/if}
</table>