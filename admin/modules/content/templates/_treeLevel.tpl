{foreach from=$objects item=item}
	<li>
		<div class="node {if !$item->haveChildren()}no-children{/if}" onclick="Leaf.selectTreeNode(this, {$item->object_data.id|escape:javascript|escape})">
			<span 
				class="expand-tool" 
				onclick="Leaf.toggleTreeNode( {$item->object_data.id|escape:javascript|escape}, this, event); return false;"
			></span>
			<span class="icon-wrap">
				<img class="icon" src="{$item->getIconUrl()|escape}" alt="" />
			</span>
			<span class="name">
				{$item->object_data.name|escape}
			</span>
		</div>
		<ul class="children unloaded"></ul>
	</li>
{/foreach}
