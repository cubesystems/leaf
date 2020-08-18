{alias_context code=$aliasContext}
<div 
	class="input selectWrap yui-skin-sam {if $selectionModel == 'search'}selectionModel-search{/if}" 
	data-searchUrl="{if $searchUrl}{$searchUrl|escape}{else}?module={$moduleName}&amp;do=all&amp;ajax=1&amp;json=1&amp;html=0{$searchUrlPostfix|escape}{/if}" 
	{if $editUrl}
		data-editUrl="{$editUrl}"
	{else}
		data-editUrl="?module={$moduleName}&amp;do=edit&amp;id=0&amp;ajax=1&amp;json=1" 
	{/if}
	{if $saveUrl}
		data-saveUrl="{$saveUrl}"
	{else}
		data-saveUrl="?module={$moduleName}&amp;do=saveAndRespond&amp;id=0&amp;ajax=1&amp;json=1" 
	{/if}
	data-dialogClass="{$dialogClass}"
	data-selectionModel="{$selectionModel}"
	data-objectsFoundText="{alias code='objectsFound'}"
	{if $selectionModel == "search"}
		data-extraResponseFields="{$extraResponseFields}"
	{/if}
>
	{strip}
		{if $selectionModel == "search"}
			<div class="autocompleteWrap">
				<input type="hidden" name="{$name|escape}" class="value {$valueFieldClass|escape}" value="{$value|escape}" />
				<input type="text" class="autocompleteInput" name="searchFor-{$name|escape}" {if $id}id="{$id|escape}"{/if} value="{$valueText|escape}" />
				<img src="{$smarty.const.SHARED_WWW}classes/input/images/select/autocompleteExpandIcon.png" class="autocompleteExpandIcon" alt="" />
				{if $creationDialog == true}
					{include file="_select.trigger.tpl"}
				{/if}
				<div class="resultsContainer"></div>
			</div>
		{else}
			<select name="{$name|escape}" {if $id}id="{$id|escape}"{/if} class="{$selectClass|escape}">
				<option value="">&nbsp;</option>
				{foreach from=$collection item=optionObject}
					<option value="{$optionObject->id|escape}"{if $optionObject->id == $value} selected="selected"{/if}>
						{$optionObject->getDisplayString()|escape}
					</option>
				{/foreach}
			</select>
			{if $creationDialog == true}
				{include file="_select.trigger.tpl"}
			{/if}
		{/if}
	{/strip}
</div>


