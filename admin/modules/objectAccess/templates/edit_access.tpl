{alias_context code=admin:objectAccess}

<ul id="leafObjectModules">
	<li>
        <a href="?module=content&amp;do=edit_object&amp;object_id={$smarty.get.object_id|escape}" class="button iconAndText" style="margin-left:50px;">
        <img src="images/icons/pencil.png" alt="" />
        {alias code=backToEdit}
        </a>
	</li>
</ul>


<div class="content">


<form method="post" class="accessForm" action="{$_module->getModuleUrl()|escape}&amp;do=save_access_rules">
<input type="hidden" name="objectId" value="{$objectId|escape}" />

{capture assign=temp}

{defun name=accessRuleRow template=false targetType="" targetId=0 value=0}
<tr class="rule{if $template} template{elseif $targetType} rule{$targetType}{/if}">

    {capture assign=targetName}{if $targetType=='General'}{alias code=targetType`$targetType`}{else}{$subjects.$targetType.$targetId|escape}{/if}{/capture}

    {assign var=inputId value="allow`$targetType``$targetId`"}

    <th class="name"><label for="{$inputId}">{$targetName}</label></th>

    <td class="value"><label><input type="checkbox" name="{$inputId}" value="1" id="{$inputId}" {if !empty($value)} checked="checked"{/if} /></label>    </td>

    <td class="remove">
        <button type="button" class="noStyling"><img src="images/icons/delete.png" title="{alias code=removeDefinition}" alt="" /></button>
    </td>

</tr>
{/defun}
{/capture}

<table class="leafTable accessRules{if !empty($accessRules)} hasRules{/if}{foreach item=targetType from=$targetTypes}{if !empty($accessRules.$targetType)} has{$targetType}Rules{/if}{/foreach}">


<thead>

    <tr>
        <th class="name">{alias code=subject}</th>
        <th class="value">{alias code=allowAccess}</th>
        <th class="remove">{alias code=removeDefinition}</th>
    </tr>

    {fun name=accessRuleRow template=true}

    <tr class="noRules">
        <td colspan="3">{alias code=noRulesDefined}</td>
    </tr>

</thead>

<tbody>
{foreach item=targetType from=$targetTypes}

    {if $targetType != 'General'}
    <tr class="heading{$targetType}">
        <th colspan="3">{alias code=targetType`$targetType`}</th>
    </tr>
    {/if}

    {foreach item=value key=targetId from=$accessRules.$targetType}
        {fun name=accessRuleRow targetType=$targetType targetId=$targetId value=$value}
    {/foreach}

{/foreach}
</tbody>

</table>


{capture assign=temp}
{defun name=addSubjectButton targetType="" targetId=0 targetName=""}

<span>
<button type="button" class="iconAndText noStyling addSubject">
    <img src="images/icons/add.png" alt="" /><span class="name">{if $targetType == 'General'}{alias code="targetType`$targetType`"}{else}{$targetName|escape}{/if}</span>
</button>
<input type="hidden" class="targetType" value="{$targetType|escape}" />
<input type="hidden" class="targetId" value="{$targetId|escape}" />
</span>

{/defun}
{/capture}


<div class="subjectsBox">
    <button type="button" class="switchSubjects">{alias code=addSubjects}</button>
    <div class="subjectsWrap">

    <ul class="block subjects">

    {foreach item=targetType from=$targetTypes}

    <li class="group subjects{$targetType}">

        {if $targetType == 'General'}
            {fun name=addSubjectButton targetType=$targetType targetId=0}
        {else}
        <h3>{alias code=targetType`$targetType`}</h3>
        <ul class="block subjects">
        {foreach key=targetId item=targetName from=$subjects.$targetType}
        <li>
            {fun name=addSubjectButton targetType=$targetType targetId=$targetId targetName=$targetName}
        </li>

        {/foreach}
        </ul>
        {/if}
    </li>


    {/foreach}
    </ul>

    <div class="clear"><!-- --></div>

    </div>
</div>


<div class="footer">
	<div class="padding">
		<button type="submit" class="iconAndText">
			<img src="images/icons/disk.png" alt="" />
			{alias code=save}
		</button>
	</div>
</div>

</form>
</div>