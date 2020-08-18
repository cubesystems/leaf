{alias_context code=$aliasContext}
<div class="special-field-wrap date-field-wrap{if $autoInit === false} no-auto-init{/if}">
	{strip}
		{if $useNormalized == true}
			<input name="{$name|escape}" {if $id}id="{$id|escape}"{/if} type="text" class="normalized" value="{$value|escape}" />
			<input name="localizedFor-{$name|escape}" {if $id}id="localizedFor-{$id|escape}"{/if} type="text" class="date localized" value="" onfocus="if( jQuery(this).hasClass('hasDatepicker') != true ) initDatepicker(this, true);" />
		{else}
			<input name="{$name|escape}" {if $id}id="{$id|escape}"{/if} type="text" class="date" value="{$value|escape}" onfocus="if( jQuery(this).hasClass('hasDatepicker') != true ) initDatepicker(this, true);" />
		{/if}
		<button type="button" class="noStyling">
			{if $removeButtonImage != true}
				{if $buttonImage}
					<img src="{$buttonImage|escape}" alt="{alias code="datePickDate"}" />
				{else}
					<img src="images/icons/date.png" alt="{alias code="datePickDate"}" />
				{/if}
			{/if}
			{$buttonText|escape}
		</button>
	{/strip}
    
    {if !$format}
        {assign var=languageCode value="properties"|leaf_get:"language_code"}
        {assign var=format value="properties"|leaf_get:"datePickerFormats":$languageCode}        
    {/if}
    
	{if $format}
		<span class="format" style="display: none;">{$format|escape}</span>
	{/if}
</div>


