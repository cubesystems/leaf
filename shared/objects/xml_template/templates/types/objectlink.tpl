<input 
	onkeyup="correctObjectLink(this)" 
	onmouseover="correctObjectLink(this)" 
	type="text" 
	name="{$field.input_name|escape}" 
	id="{$field.input_id|escape}" 
	value="{$field.value|escape}"  
	onchange="updateObjectFieldPreview(this);" 
/>
<span style="cursor: pointer;"
	onclick="Leaf.openLinkDialog(this, event, '?module=content&amp;do=object_manager&amp;type=link&amp;target_id={$field.input_id|escape:javascript|escape}'); return false;"
>
	<img src="{$smarty.const.WWW|escape}images/icons/world_link.png" alt="from site" />
</span>
<span class="objectPreview">
	{if $field.preview}
		{include file=objectFieldPreview.tpl}	
	{/if}
</span>
