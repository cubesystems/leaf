{* TODO: deprecate this *}
{if $name == 'name'}{assign var=labelContext value="admin:desktop"}{else}{assign var=labelContext value=null}{/if}
<div class="field">
	<div class="labelWrap">
		<label for="{$name|escape}{$namespace}">
			{if $alias}
				{alias code=$alias context=$labelContext}:{if $required} *{/if}
			{else}
				{alias code=$name context=$labelContext}:{if $required} *{/if}
			{/if}
		</label>
	</div>
	<input
		name="{$name|escape}" id="{$name|escape}{$namespace}" value="{$item->$name|escape}" type="text"
		class="focusOnReady titleField"
	/>
</div>