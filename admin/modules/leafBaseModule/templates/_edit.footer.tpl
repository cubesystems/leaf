<div class="footer center">
	{* TODO: figure out a way to re-use this fragment *}
	{strip}
		{if $smarty.get.returnUrl}
			{assign var=returnUrl value=$smarty.get.returnUrl}
		{elseif $item->id != 0}
			{assign var=moduleUrl value=$_module->getModuleUrl()}
			{assign var=returnUrl value="`$moduleUrl`&do=view&id=`$item->id`"}
		{else}
			{assign var=returnUrl value=$smarty.server.HTTP_REFERER}
		{/if}
		{if $smarty.get.listUrl}
			{assign var=listUrlEncoded value=$smarty.get.listUrl|urlencode}
			{assign var=listUrl value=$smarty.get.listUrl}
			{assign var=returnUrl value="`$returnUrl`&listUrl=`$listUrlEncoded`"}
			<input type="hidden" name="listUrl" value="{$listUrl|escape}" />
		{elseif $item->id == 0}
			{assign var=listUrl value=$_module->getModuleUrl()}
			<input type="hidden" name="listUrl" value="{$listUrl|escape}" />
		{/if}
		{if $smarty.get.returnOnSave}
			<input type="hidden" name="returnOnSave" value="{$smarty.get.returnOnSave|escape}" />
		{/if}
	{/strip}
	
	<div class="block leftBlock">
		<a class="button iconAndText cancelButton" href="{$returnUrl|escape}">
			<img src="images/icons/arrow_left.png" alt=""/>
			{alias code="cancel"}
		</a>
	</div>
	<div class="block centerBlock">
		<button type="submit" class="iconAndText saveButton">
			<img src="images/icons/disk.png" alt=""/>{alias code="save"}
		</button>
	</div>
	{*<div class="block rightBlock"></div>*}
</div>