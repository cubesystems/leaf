<div class="footer">
	<div class="padding">
		{strip}
			{if $smarty.get.listUrl}
				<a href="{$smarty.get.listUrl|escape}" style="margin-right: 10px;" class="button icon-and-text">
					<img src="images/icons/arrow_left.png" alt="" />
					{alias code="back"}
				</a>
				{assign var=listUrl value=$smarty.get.listUrl|urlencode}
				{assign var=listUrlArg value="&listUrl=`$listUrl`"}
			{else}
				<span class="button-group compact" style="margin-right: 8px;" >
					<button disabled="disabled" class="icon-and-text" style="padding-left: 4px;">
						<img src="images/icons/arrow_left.png" alt="" />
						{alias code="back"}
					</button>
				</span>
			{/if}
			{if $_module->features.edit == true}
				<a href="{$_module->getModuleUrl()|escape}&amp;do=edit&amp;id={$item->id|escape}{$listUrlArg|escape}" class="button icon-and-text">
					<img src="images/icons/page_white_edit.png" alt="" />
					{alias code="edit"}
				</a>
			{/if}
			
			{include file=$_module->pathTo('view._extras')}
			
			<span style="vertical-align: 0px; visibility: hidden;">&nbsp;</span>
			{if $_module->features.delete == true}
				<form action="{request_url add='do=delete' remove='listUrl'}" class="deleteForm" method="post">
					{if $smarty.get.listUrl}
						<input type="hidden" name="returnUrl" value="{$smarty.get.listUrl|escape}" /> {* returnUrl = confirmation success url *}
					{/if}
                    <input type="hidden" name="listUrl" value="{request_url}" /> {* listUrl = confirmation cancel url *}
					<button class="icon-and-text deleteButton" type="submit">
						<img src="images/icons/bin_empty.png" class="icon" alt=""/>
						{alias code="delete"}
					</button>
				</form>
			{/if}
			
		{/strip}
	</div>
</div>