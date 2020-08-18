<input 
	type="file" name="{$field.input_id|escape}_file" 
	onchange="{if $field.properties.allowedExtensions}checkUploadFileExtension(this, '{$field.properties.allowedExtensions|escape:javascript|escape}');{/if}updateLinkedFieldsSwitch(this, '{$field.input_id|escape:javascript|escape}', '{$field.input_name|escape:javascript|escape}');" 
/>
<input 
	onkeyup="correctObjectLink(this);jQuery(this).change();" 
	onmouseover="correctObjectLink(this);jQuery(this).change();" 
	type="text" 
	name="{$field.input_name|escape}" 
	class="short fileobject" 
	id="{$field.input_name|escape}" 
	value="{$field.value|escape}" 
/>
{if $field.properties.dialog}
	{input type=filepicker for=$field.input_name}
{/if}
{if $field.linked_fields && !$field.properties.local_import}
	<label for="{$field.input_id|escape}_update_linked" id="{$field.input_id|escape}_update_linked_switch" style="display: none">
		<input type="checkbox" name="{$field.input_id|escape}_update_linked" id="{$field.input_id|escape}_update_linked" value="1" />
		{alias code=update_linked_fields context="admin:contentObjects"}
	</label>
{/if}
{if $field.properties.local_import && $field.properties.local_import_options}
	<div>
		<label for="{$field.input_id|escape}_local_import">Uploaded file: </label>
		<select name="{$field.input_id|escape}_local_import" id="{$field.input_id|escape}_local_import">
		    <option value="">-</option>
		    {foreach item=fileName from=$field.properties.local_import_options}
				<option value="{$fileName|escape}">{$fileName|escape}</option>
		    {/foreach}
		</select>
	</div>
{/if}
{if $field.file}
	<div>
		<a target="_blank" href="{$field.file.file_www|escape}{$field.file.file_name|escape}" class="filename">
			{$field.file.name|escape} ({$field.file.file_name|escape})
		</a>
	</div>
{/if}
{if in_array($field.file.extension, array('jpg', 'png', 'gif'))}
	<div class="imageBox">
		<a target="_blank" href="{$field.file.file_www|escape}{$field.file.file_name|escape}">
			<img src="{$field.file.file_www|escape}{if $field.file.extra_info.thumbnail_size}thumb_{/if}{$field.file.file_name|escape}" alt="" />
		</a>
	</div>
{elseif $field.file.extension == 'swf'}
	<div>
		<object type="application/x-shockwave-flash" data="{$field.file.file_www|escape}{$field.file.file_name|escape}" width="{$field.file.extra_info.image_width|escape}" height="{$field.file.extra_info.image_height|escape}" >
			<param name="quality" value="high" />
			<param name="bgcolor" value="#FFFFFF" />
			<param name="wmode" value="transparent" />
			<param name="movie" value="{$field.file.file_www|escape}{$field.file.file_name|escape}" />
		</object>
	</div>
{/if}

