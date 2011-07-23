{* @param CourseSequence $cs *}
{* @param INTEGER $year *}

{extends file="courseSequence.tpl"}
{block name="classInfo"}
    {foreach $cs->semesters as $semester}
        <td valign="top" class="semesterClasses" id="{$semester->getUID()}" data-id="{$semester->getUID()}" data-hours="{$semester->hours - $semester->completedHours}">
            {include file="semester.tpl" semester=$semester}
        </td>
        {if $semester->semesterID == 1}
            </tr><tr>
        {/if}
    {/foreach}
{/block}