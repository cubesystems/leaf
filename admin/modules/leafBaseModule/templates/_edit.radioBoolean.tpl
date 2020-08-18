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
	<div class="value checkboxGroup">
		<input type="radio" name="{$name}" id="{$name}-1-{$namespace}" value="1" {if $item->$name}checked="checked"{/if} />
		<label for="{$name}-1-{$namespace}">
			{alias code="yes"}
		</label>
		
		<input type="radio" name="{$name}" id="{$name}-0-{$namespace}" value="0" {if $item->$name == false}checked="checked"{/if} />
		<label for="{$name}-0-{$namespace}">
			{alias code="no"}
		</label>
	</div>
	
</div>