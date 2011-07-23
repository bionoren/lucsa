{extends file="courseSequence.tpl"}
{block name="classInfo"}
    {$allClasses = $cs->classes}
    {$allClasses->sort()}
    {$count = floor(count($allClasses)/2)}
    <tr style="vertical-align:top;">
        <td>
            <table>
                {foreach $allClasses as $class}
                    {include file="course.tpl" class=$class}
                    {if $class@index == $count}
                        </table></td><td><table>
                    {/if}
                {/foreach}
            </table>
        </td>
    </tr>
{/block}