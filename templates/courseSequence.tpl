{* @param CourseSequence $cs *}
{* @param INTEGER $year *}

<table id="{$cs->ID}">
    <tr>
        <td colspan="3" class="majorTitle">
            <a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year={$year}&degree={$cs->linkID}">{$cs->name} ({$cs->acronym})</a>
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
        <td colspan="3" style="text-align:center;">
            Completed Hours: <span id="{$cs->ID}-completedHours">{$cs->completedHours}</span>
            <br/>
            Remaining Hours: <span id="{$cs->ID}-remainingHours">{$cs->hours-$cs->completedHours}</span>
            <br/>
            Total Hours: {$cs->hours}
        </td>
    </tr>
    {if $cs->notes->count() > 0}
        <tr>
            <td colspan="3" class="endNote">
                Notes:
            {foreach $cs->notes->notes as $i=>$note}
                <br/>
                <span class="endNote">{$i}</span>: {$note}
            {/foreach}
            </td>
        </tr>
    {/if}
</table>