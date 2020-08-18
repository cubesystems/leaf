<table class="leafTable noBorder labelFirstColumn aliases aliasesTable" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th class="codeHeader">{alias code=name}</th>
			{foreach from = $_module->options.languages key = language_id item = language_name}
				<th class="languageHeader languageId-{$language_id|escape} {*{if is_array($expandedLangs) &&  in_array($language_id, $expandedLangs)}expanded{/if}*}" data-languageId="{$language_id|escape}" {if $_module->isLanguageVisible($language_id)==false}style="display:none;"{/if}>
					<span class="name">{$language_name|escape}</span>
				</th>
			{/foreach}
			<th class="deleteHeader">&nbsp;</th>
		</tr>
	</thead>
	<tbody id="variables">

		{foreach from=$translations item=translation}
			<tr id="translation_{$translation.id|escape}">
				<td class="translation_name codeColumn">
					<input type="hidden" name="translations_type[]" value="{$translation.type|escape}" class="short" />
					<input type="hidden" name="translations_id[]" value="{$translation.id|escape}" />
					<input type="text" name="translations_name[]"  value="{$translation.name|escape}" title="{$translation.name|escape}"/>
				</td>
				{foreach from=$_module->options.languages key=language_id item=language_name}
					<td id="translation_{$language_id|escape}_{$translation.id|escape}" class="translationCell{if $translation.machine[$language_id]} machineTranslated{/if} languages col_{$language_id|escape} languageId-{$language_id|escape} languageCode-{$language_name|escape} {*{if is_array($expandedLangs)}{if in_array($language_id, $expandedLangs)} expanded{/if}{/if}*}" data-languageId="{$language_id|escape}" data-languageCode="{$_module->options.languages.$language_id|escape}" {if $_module->isLanguageVisible($language_id)==false}style="display:none;"{/if}>
					<div class="wrap">
						<input type="text" class="translationText" name="translations_lang_{$language_id|escape}[]" value="{$translation.languages[$language_id]|escape:'html'}" title="{$language_name|escape}:{$translation.name|escape}" />
					</div>

					<input type="hidden" class="machineTranslation" name="translations_lang_{$language_id|escape}_machine[]" value="{$translation.machine[$language_id]|escape}" />
					</td>
				{/foreach}
				<td class="deleteColumn">
					<span style="cursor:pointer;" onclick="removeEntry(this)">
						<img src="images/icons/delete.png" alt="" />
					</span>
				</td>
			</tr>
		{/foreach}
	</tbody>
</table>