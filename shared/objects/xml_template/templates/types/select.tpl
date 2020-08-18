<select id="{$field.input_id|escape}" name="{$field.input_name|escape}{if $field.properties.multiple}[]{/if}"{if $field.properties.multiple} multiple="multiple"{/if}{if $field.properties.size>1} size="{$field.properties.size|escape}"{/if}>
{if $field.properties.multiple}
{foreach item=text key=key from=$field.properties.options}
<option label="{$text|escape}" value="{$key|escape}" {if in_array($key, $field.value)} selected="selected"{/if}>{$text|escape}</option>
{/foreach}
{else}
{html_options options=$field.properties.options selected=$field.value}
{/if}
</select>