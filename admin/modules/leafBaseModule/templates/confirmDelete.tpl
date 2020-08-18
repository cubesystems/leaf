<div class="header">
	<div class="padding">
		<h2>{$item->getDisplayString()|escape}</h2>
	</div>
</div>
<div class="content outsetBox">
    {if $deletionAllowed}
	<div class="question">{alias code="confirmDelete"}</div>
    {else}
    <div class="warning">{alias code="deletionNotAllowed"}</div>
    {/if}
    
	<div class="description">
		{if $item->getDisplayString() == ""}
			&quot;{$item|get_class|escape} id:{$item->id|escape}&quot;
		{else}
			&quot;{$item->getDisplayString()|escape}&quot;
		{/if}
	</div>
	
	<div class="buttons">
        {if $deletionAllowed}
		<form action="{request_url add='do=delete'}" class="deleteForm" method="post">
			{if $smarty.get.returnUrl}
				<input type="hidden" name="returnUrl" value="{$smarty.get.returnUrl|escape}" />
			{/if}
			<input type="hidden" name="confirm" value="1" />
			<button class="icon-and-text deleteButton" type="submit">
				<img src="images/icons/bin_empty.png" class="icon" alt=""/>
				{alias code="confirmDeleteYes"}
			</button>
		</form>
        {/if}
        
		{if $smarty.get.listUrl}
			{assign var=listUrl value=$smarty.get.listUrl}
		{elseif $smarty.server.HTTP_REFERER}
			{assign var=listUrl value=$smarty.server.HTTP_REFERER}
        {else}
            {assign var=listUrl value=$_module->getModuleUrl()}
		{/if}
		<a class="button" href="{$listUrl|escape}">
			{if $deletionAllowed}{alias code="confirmDeleteNo"}{else}{alias code=return}{/if}
		</a>
	</div>
</div>
