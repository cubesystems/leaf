{if $editor == "tinymce"}
	<textarea onchange="alert('changed')" rows="10" cols="50" class="leafRichtext" style="height:{if $field.properties.height}{$field.properties.height|escape}{else}500px{/if}; width: 96%;" id="{$field.input_id|escape}" name="{$field.input_name|escape}">{$field.value|trim|escape}</textarea>
{else}
	<textarea rows="10" cols="50" style="height:{if $field.properties.height}{$field.properties.height|escape}{else}500px{/if}; width: 96%;" id="{$field.input_id|escape}" name="{$field.input_name|escape}"></textarea>
	<div class="hiddenFields" id="sourceDiv{$field.input_id|escape}">
		{$field.value|trim}
	</div>
	<div style="display:none" class="mceResizeBox">
		asd
	</div>
	<script type="text/javascript">
		richtextEditors.push('{$field.input_id|escape:javascript}');
		{if $field.properties.xinha_plugins}
			richtextEditorPlugin.push(Array('{$field.input_id|escape:javascript}', '{$field.properties.xinha_plugins|escape:javascript}'));
		{/if}
		{if $field.properties.xinha_css}
			richtextEditorCss.push(Array('{$field.input_id|escape:javascript}', '{$field.properties.xinha_css|escape:javascript}'));
		{/if}
		{if $field.properties.xinha_toolbar}
			richtextEditorToolbar.push(Array('{$field.input_id|escape:javascript}', '{$field.properties.xinha_toolbar|escape:javascript}'));
		{/if}
		{if $field.properties.xinha_stylist_css}
			richtextEditorStylistCss.push(Array('{$field.input_id|escape:javascript}', '{$field.properties.xinha_stylist_css|escape:javascript}'));
		{/if}
	</script>
{/if}
