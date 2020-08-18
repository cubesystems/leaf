<table id="impoertedTranslations">
{foreach from=$translations item=translation}
    <tr>
        <td class="translation_name">
            <input type="hidden" name="translations_type[]" value="{$translation.type|escape}" class="short" />
            <input type="hidden" name="translations_id[]" value="{$translation.id|escape}" />
            <input type="text" name="translations_name[]"  value="{$translation.name|escape}" title="{alias code=name}"/>
        </td>
        {foreach from = $_module->options.languages key = language_id item = language_name}
            <td id="translation_{$language_id|escape}_{$translation.id|escape}" class="translationCell{if $translation.machine[$language_id]} machineTranslated{/if} languages col_{$language_id|escape} languageId-{$language_id|escape} languageCode-{$language_name|escape} {*{if is_array($expandedLangs)}{if in_array($language_id, $expandedLangs)} expanded{/if}{/if}*}" data-languageId="{$language_id|escape}" data-languageCode="{$_module->options.languages.$language_id|escape}" {if $_module->isLanguageVisible($language_id)==false}style="display:none;"{/if}>
            <div class="wrap">
                <input type="text" class="translationText" name="translations_lang_{$language_id|escape}[]" value="{$translation.languages[$language_id]|escape:'html'}" title="{$language_name|escape}:{$translation.name|escape}"/>
            </div>
            <input type="hidden" class="machineTranslation" name="translations_lang_{$language_id|escape}_machine[]" value="{$translation.machine[$language_id]|escape}" />
            </td>
        {/foreach}
        <td>
            <a href="#" onclick="return removeEntry(this)">
                <img src="images/icons/delete.png" alt="" />
            </a>
        </td>
    </tr>
{/foreach}
</table>

