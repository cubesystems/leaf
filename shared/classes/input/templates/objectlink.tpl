{alias_context code=$aliasContext}
<div class="specialFieldWrap objectlinkFieldWrap">
	{strip}
		<input name="{$name|escape}" 
			{if $id}id="{$id|escape}"{/if} 
			type="text" 
			class="objectlink" 
			value="{$value|escape}"
			onkeyup="objectlink.extractIdFromUrl(this);" 
			onmouseover="objectlink.extractIdFromUrl(this);" 
		/>
		<button type="button" onclick="objectlink.openDialog(this, event); return false;">
			{if $removeButtonImage != true}
				{if $buttonImage}
					<img src="{$buttonImage|escape}" alt="{alias code='pickObject'}" />
				{else}
					<img src="images/icons/world_link.png" alt="{alias code='pickObject'}" />
				{/if}
			{/if}
			{$buttonText|escape}
		</button>
	{/strip}
</div>