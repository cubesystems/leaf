{alias_context code=leafFile}
<div class="leafFile-field-wrap{if !empty($file) && $file != "-1"} field-has-leafFile{/if}">
    <input type="hidden" name="{$name|escape}{if $multiple}[{$index|escape}]{/if}"{if $id} id="{$id|escape}"{/if} value="{$file|default:-1}" class="leafFile-id-field" />
    <input type="hidden" class="leafFile-remove-confirmation-field" value="{if isset($removeConfirmationMessage)}{$removeConfirmationMessage}{else}{alias code=confirmationRemoveFile}{/if}" />
    <span class="leafFile-input-wrap">
        <input type="file" name="{$name|escape}{$customSuffix|default:$inputFieldSuffix|escape}{if $multiple}[{if !$selectMultiple}{$index|escape}{/if}]{/if}" {if $id}id="{$id|escape}{$inputFieldSuffix|escape}"{/if} class="file" />
    </span>
    {if $fileInstance}
        <div class="leafFile-preview-wrap">
            {if $previewLink}
				<a href="{$previewLink|escape}">
					{$fileInstance->getFileName()|escape}
				</a>
			{else}
				 {$fileInstance->getFileName()|escape}
			{/if}
            {capture assign=removeLabel}{if isset($removeFileLabel)}{$removeFileLabel}{elseif $removeFileAlias}{alias code=$removeFileAlias context=$removeFileAliasContext}{else}{alias code=removeFile}{/if}{/capture}
            <button type="button" class="removeFileButton" title="{$removeLabel}" >
                {if $buttonImage}
					<img src="{$buttonImage|escape}" alt="{$removeLabel}" />
                {elseif $buttonText}
                    <span>{$removeLabel}</span>
				{else}
					<img src="images/icons/bin_empty.png" alt="{$removeLabel}" />
				{/if}
    		</button>
        </div>
    {/if}
    {$content}
</div>
