<div class="thead">
	<div>
		<span>{alias code=title}</span>
		<span>
			{alias code=isActive}
		</span>
		<span>{alias code=complete}</span>
	</div>
</div>
<div class="tbody">
	{foreach from=$collection item=item name=name}
		<a href="{include file=$_module->pathTo('all._url')}">
			<span>{$item->title|escape}</span>
			<span>
				{if $item->isActive}
					<img src="images/icons/tick.png" alt="" />
				{else}
					<img src="images/icons/cross.png" alt="" />
				{/if}
			</span>
			<span>
				{if $item->complete}
					<img src="images/icons/tick.png" alt="" /> ({$item->completeDate})
				{else}
                    -
				{/if}
			</span>
		</a>
	{/foreach}
</div>
