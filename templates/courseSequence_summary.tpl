{* @param CourseSequence $cs *}
{* @param INTEGER $year *}

{extends file="courseSequence.tpl"}
{block name="classInfo"}
    {foreach $cs->getSemesters() as $semester}
        <td valign="top" class="semesterClasses" id="{$semester->getUID()}" data-id="{$semester->getUID()}" data-hours="{$semester->getHours() - $semester->getCompletedHours()}">
            {include file="semester.tpl" semester=$semester}
        </td>
        {if $semester->getID() == Semester::$SEMESTERS[0]}
            </tr><tr>
        {/if}
    {/foreach}
{/block}