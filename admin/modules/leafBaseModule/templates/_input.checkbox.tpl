<div class="field {$fieldWrapClass}">
	<div class="labelWrap">
		<label for="{$name|escape}{$namespace}">
			{if $alias}
				{alias code=$alias}:
			{else}
				{alias code=$name}:
			{/if}
		</label>
		{if $descriptionAlias}
			<div class="description">
				{alias code=$descriptionAlias}
			</div>
		{/if}
	</div>
	<input type="checkbox" name="{$name|escape}" id="{$name|escape}{$namespace}" value="1" class="{$className}" {if $item->$name} checked="checked"{/if}/>
</div>