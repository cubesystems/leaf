{if !$page_var}
	{assign var="page_var" value="page"}
{/if}
{*{if $pageNavigation->pageCount > 1}*}
	<div class="page-navigation-box-wrap">
		<div class="page-navigation-box">
		    {*<span class="label">
				{alias code="pages" context="pages_admin"}:
			</span>*}
			{strip}
				<div class="page-previous">
					{if $pageNavigation->previous}
						<a href="{request_url add="`$page_var`=`$pageNavigation->previous`" remove="ajax"}" class="button">
							{*{alias code='previous' context='pages_admin'}*}
							&lt;
						</a>
					{else}
						<span class="button">
							&lt;
						</span>
					{/if}
				</div>
				
			    <ol class="block page-navigation">
				    {foreach from=$pageNavigation->pages item=item}
					    {strip}
					        <li class="page{if $item.skipped} skipped{/if}{if $item.active} active{/if}">
						        {if $item.skipped}
									<span class="button">
										...
									</span>
						        {else}
									<a href="{request_url add="`$page_var`=`$item.number`" remove="ajax"}" class="button{if $item.active} active{/if}">
										{$item.number}
									</a>
						        {/if}
					        </li>
					    {/strip}
				    {/foreach}
			    </ol>
				
				<div class="page-next">
					{if $pageNavigation->next}
						<a href="{request_url add="`$page_var`=`$pageNavigation->next`" remove="ajax"}" class="button">
							&gt;
						</a>
					{else}
						<span class="button">
							&gt;
						</span>
					{/if}
				</div>
				
			{/strip}
			
		</div>
	</div>
{*{/if}*}