<div class="field {$fieldClass}">
	<div class="labelWrap">
		<label for="{$name|escape}{$namespace}">{alias code=$name}:</label>
        {if $descriptionAlias}
            <div class="description">
                {alias code=$descriptionAlias}
            </div>
        {/if}        
	</div>
	<textarea name="{$name|escape}" id="{$name|escape}{$namespace}" {if $textareaClass}class="{$textareaClass}"{/if} rows="5" cols="50">{if isset($value)}{$value|escape}{else}{$item->$name|escape}{/if}</textarea>
</div>
