<div class="header">
	<div class="padding">
		<h2>
			{if $overrideAlias && $context}
				{alias code=$overrideAlias context=$context}
			{elseif $overrideAlias}
				{alias code=$overrideAlias}
			{elseif $item->id == 0}
				{alias code="new"}
			{elseif $overrideName}
				{$overrideName|escape}
			{else}
				{$item->getDisplayString()|escape}
			{/if}
		</h2>
	</div>
</div>