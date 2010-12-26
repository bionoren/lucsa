{extends file="courseSequence.tpl"}
{block name="classInfo"}
    {foreach $cs->getSemesters() as $semester}
        <td valign="top">
            {include file="semester.tpl" semester=$semester}
        </td>
        {if $semester->getID() == Semester::$SEMESTERS[0]}
            </tr><tr>
        {/if}
    {/foreach}
{/block}