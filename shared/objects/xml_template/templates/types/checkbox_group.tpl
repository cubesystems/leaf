<fieldset>
{foreach from = $field.properties.options key = option_value item = option_name name = options}
<div class="sideLabel">
    <input id="{$field.input_id|escape}_{$smarty.foreach.options.iteration}" {if in_array($option_value, (array) $field.value)}checked="checked"{/if} type="checkbox" name="{$field.input_name|escape}[]" value="{$option_value|escape}" />
    <label for="{$field.input_id|escape}_{$smarty.foreach.options.iteration}">{$option_name|escape}</label>
</div>
{/foreach}
</fieldset>