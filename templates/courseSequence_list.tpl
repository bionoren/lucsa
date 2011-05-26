{extends file="courseSequence.tpl"}
{block name="classInfo"}
    {$allClasses = $cs->getClasses()}
    {$allClasses->sort()}
    {$i = 0}
    {$count = floor($allClasses->count()/2)}
    <tr style="vertical-align:top;">
        <td>
            <table>
                {foreach $allClasses as $class}
                    {include file="course.tpl" class=$class}
                    {if $i++ == $count}
                        </table></td><td><table>
                    {/if}
                {/foreach}
            </table>
        </td>
    </tr>
{/block}