{* @param ARRAY $subClasses List of classes that have been taken. *}
{* @param INTEGER $year *}
{* @param ARRAY $years List of years that have data *}
{* @param ARRAY $majors List of majors that are offered for $year *}
{* @param ARRAY $degree List of degrees the user is currently enrolled in *}
{* @param ARRAY $courseSequences List of course sequences for $year *}

{extends file="page.tpl"}
{block name="body"}
<div id="classSubs" style="float:left; width:250px; font-size:12px;">
    <form method="post" action=".">
        <input type="hidden" name="reset" value="1">
        <input type="hidden" name="year" value="{$year}">
        <input type="submit" name="autocomplete" value="Autocomplete"/>
        <br/><br/>
    </form>
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
<div style="display:inline;">
    <form method="post" action=".">
        <input type="submit" name="clearClasses" value="Clear All Class Associations">
    </form>
    {if $courseSequences}
        {$courseSequence = $courseSequences[0]}
        {if empty($smarty.get.disp) || $smarty.get.disp == "summary"}
            {include file="courseSequence_summary.tpl" cs=$courseSequence}
        {elseif $smarty.get.disp == "list"}
            {include file="courseSequence_list.tpl" cs=$courseSequence}
        {/if}
        <br/>
    {/if}
    <form method="get" action=".">
        <div>
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

            <input type="submit" name="submit" value="Update"/>
        </div>
    </form>
</div>
{/block}