<input 
	type="text" 
	id="{$field.input_id|escape}" 
	{if $field.properties.size}
		style="width:{$field.properties.size|escape}px;"
	{/if} 
	name="{$field.input_name|escape}" 
	value="{$field.value|escape}" 
/>