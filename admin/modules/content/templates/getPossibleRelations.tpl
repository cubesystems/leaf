{if count($collection) > 0}
	<ul class="block nodes">
		{foreach from=$collection item=item}
			<li class="node" data-id="{$item->object_data.id}">
				<img class="icon" src="{$item->getIconUrl()}" alt=""/>
				{$item->object_data.name|mb_truncate:"UTF8":22:"..."|escape:"html"}
			</li>
		{/foreach}
	</ul>
	<div class="creatingRelationMessage" style="display:none;">
		<img src="images/loader.gif" alt="" class="loader" />
		{alias code=creatingRelation}
	</div>
{else}
	no is
{/if}