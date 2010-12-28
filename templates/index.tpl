{extends file="page.tpl"}
{block name="body"}
<div id="classSubs" style="float:left; width:250px; font-size:12px;">
    {$dept = null}
    {$lastTitle = ""}
    {foreach $subClasses as $class}
        {if $class->getDepartment() != $dept}
            {$dept = $class->getDepartment()}
            <div class="deptHeader">{$dept}</div>
        {/if}
        {if $lastTitle != $class->getTitle()}
            {$lastTitle = $class->getTitle()}
            <div id="{$class->getUID()}" class="incompleteClass">
                {include file="course_sub.tpl"}
            </div>
        {/if}
    {/foreach}
</div>
<div style="display:inline; float:left;">
    <form method="get" action=".">
        Year: <select name="year">
            {foreach $years as $yr}
                <option value='{$yr}'{if $yr == $year} selected='selected'{/if}>{$yr}</option>
            {/foreach}
        </select>
        <br/>
        Major: <select name="degree[]">
            <optgroup label="-- Majors">
                {foreach $majors as $key=>$deg}
                    <option value="{$key}"{if is_array($degree) && in_array($key, $degree)} selected='selected'{/if}>{$deg.name} ({$key})</option>
                {/foreach}
            </optgroup>
        </select>
        <br/>

        <input type="submit" name="submit" value="submit"/>
    </form>
    <br/>

    {if $courseSequences}
        {$courseSequence = $courseSequences[0]}
        {if empty($smarty.get.disp) || $smarty.get.disp == "summary"}
            {include file="courseSequence_summary.tpl" cs=$courseSequence}
        {/if}
        <br/>
    {/if}
</div>
{/block}