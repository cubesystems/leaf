{if !$page_var}
	{assign var="page_var" value="page"}
{/if}
{*{if $pageNavigation->pageCount > 1}*}
	<div class="toolbar-wrap page-navigation-box-wrap">
		<div class="toolbar page-navigation-box">
		    <span class="label">
				{alias code="pages" context="pages_admin"}:
			</span>
			{strip}
				{if $pageNavigation->previous}
				    <div class="page-previous">
						<a href="{request_url add="`$page_var`=`$pageNavigation->previous`"}">
							{alias code="previous" context="pages_admin"}
						</a>
				    </div>
			    {/if}
			    <ol class="block page-navigation">
				    {foreach from=$pageNavigation->pages item=item}
					    {strip}
					        <li class="page{if $item.skipped} skipped{/if}{if $item.active} active{/if}">
						        {if $item.skipped}
									...
						        {else}
									<a href="{request_url add="`$page_var`=`$item.number`"}">
										{$item.number|escape}
									</a>
						        {/if}
					        </li>
					    {/strip}
				    {/foreach}
			    </ol>
				{if $pageNavigation->next}
				    <div class="page-next">
						<a href="{request_url add="`$page_var`=`$pageNavigation->next`"}">
							{alias code="next" context="pages_admin"}
						</a>
				    </div>
			    {/if}
			{/strip}
			
		</div>
	</div>
{*{/if}*}