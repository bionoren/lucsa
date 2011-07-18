{* @param ARRAY $subClasses List of classes that have been taken. *}
{* @param INTEGER $year *}
{* @param ARRAY $years List of years that have data *}
{* @param ARRAY $majors List of majors that are offered for $year *}
{* @param ARRAY $degree List of degrees the user is currently enrolled in *}
{* @param ARRAY $courseSequences List of course sequences for $year *}
{* @param BOOLEAN $activated True if the user has logged in. *}

{extends file="page.tpl"}
{block name="body"}
<ul id="tabbar" class="subsection_tabs">
    {foreach $tabs as $tab}
        <li class="tab"><a href="#tab{$tab@iteration}">Tab {$tab@iteration}</a></li>
    {/foreach}
    <li class="tab"><a href="#newTab">+</a></li>
</ul>
{foreach $tabs as $tab}
    <div id="tab{$tab@iteration}">
        <div id="classSubs{$tab@iteration}" style="float:left; width:250px; font-size:12px;">
            {if count($tab->getSubstitutes()) > 1}
                <form method="post" action=".">
                    <input type="hidden" name="reset" value="1"/>
                    <input type="hidden" name="year" value="{$year}"/>
                    <input type="submit" name="autocomplete" value="Autocomplete"/>
                </form>
            {/if}
            <h3>Classes Taken:</h3>

            {if !$activated}
                Login with your LETU username and password to access all of your classes!
                {if $smarty.request.login}
                    <br/>
                    <span class="error">Invalid username/password</span>
                {/if}
                <form method="post" action=".">
                    Username: <input type="text" name="username"/>
                    <br/>
                    Password: <input type="password" name="password"/>
                    <br/>
                    <input type="submit" name="login" value="Get My Classes"/>
                </form>
                <br/><br/>
            {/if}

            {$dept = null}
            {$lastTitle = ""}
            {foreach $tab->getSubstitutes() as $class}
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
            <br/>
            <form method="post" action="." id="clearClassesForm{}">
                <input type="submit" name="clearClasses" value="Clear All Class Associations" onclick="return clearClassConfirm('clearClassesForm')"/>
            </form>
        </div>
        <div style="display:inline;">
            {foreach $tab->getDegrees() as $courseSequence}
                {if empty($smarty.get.disp) || $smarty.get.disp == "summary"}
                    {include file="courseSequence_summary.tpl" cs=$courseSequence}
                {elseif $smarty.get.disp == "list"}
                    {include file="courseSequence_list.tpl" cs=$courseSequence}
                {/if}
            {/foreach}
            <br/>
            <form method="get" action=".">
                <div>
                    Year: <select name="year">
                        {foreach $years as $yr}
                            <option value='{$yr}'{if $yr == $year} selected='selected'{/if}>{$yr}</option>
                        {/foreach}
                    </select>
                    <br/>
                    {$degrees = $tab->getDegrees()}
                    Major: <select name="degree[]">
                        {foreach $majors as $key=>$deg}
                            <option value="{$key}"{if array_key_exists($deg.ID, $degrees)} selected='selected'{/if}>{$deg.name} ({$key})</option>
                        {/foreach}
                    </select>
                    <br/>
                    <input type="submit" name="submit" value="Update"/>
                </div>
            </form>
        </div>
    </div>
{/foreach}
<div id="newTab"></div>
{/block}