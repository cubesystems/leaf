{* TODO: refactor this *}
<div class="field noLabelWrapPadding {if $nl2br || $indentValue}indentValue{/if}">
	<div class="labelWrap">
		<div class="label">
			{assign var=aliasString value=$name}
			{if $alias}
				{assign var=aliasString value=$alias}
			{/if}
			{if $aliasContext}
				{alias code=$aliasString context=$aliasContext}:
			{else}
				{alias code=$aliasString}:
			{/if}
		</div>
		{if $description}
			<div class="description">
				{$description}
			</div>
		{/if}
	</div>
	<div class="value">
		{if is_object( $item->$name )}
			<a href="?module={$module}&amp;do=view&amp;id={$item->$name->id}">
				{$item->$name->getDisplayString()|escape}
			</a>
		{else}
			-
		{/if}
	</div>
	<div class="clear"></div>
</div>