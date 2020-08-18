{php}$this->assign('languageList', leafLanguage::getLanguages());{/php}
<div class="field i18nInput {if $type=='textarea'}textFieldContainer{/if} {if $type=='richtext'}textFieldContainer richtextField{/if} {$fieldWrapClass}">
	<div class="labelWrap">
		<label for="{$name|escape}{$namespace}">
			{assign var=aliasString value=$name}
			{if $alias}
				{assign var=aliasString value=$alias}
			{/if}
			{if $aliasContext}
				{alias code=$aliasString context=$aliasContext}:
			{else}
				{alias code=$aliasString}:
			{/if}
		</label>
		{if $descriptionAlias}
			<div class="description">
				{alias code=$descriptionAlias vars=$descriptionAliasVars}
			</div>
		{/if}
	</div>
	<div class="value">
		<div class="i18nWrap">
			<div class="inputWrap">
				{foreach from=$languageList item=language name=i18nInputs}
					{if $propertyName}
						{assign var=value value=$item->getI18NValue($propertyName, $language->code)}
					{elseif is_object($item)}
						{assign var=value value=$item->getI18NValue($name, $language->code)}
					{/if}
					{assign var=i18nName value="i18n:`$language->code`:`$name`"}
					{if $type=="textarea" || $type=="richtext"}
						<div class="input" 
							data-language="{$language->code|escape}"
							{if $smarty.foreach.i18nInputs.iteration!=1}style="display:none"{/if}
						>
							<textarea
								name="{$i18nName|escape}"
								id="{$i18nName|escape}{$namespace}"
								class="{$className} {if $type=='richtext'}richtextInput{/if}"
								{if $readonly}readonly="readonly"{/if}
								{if $disabled}disabled="disabled"{/if}
								cols="50" rows="5"
							>{if isset($value)}{$value|escape}{else}{$item->$i18nName|escape}{/if}</textarea>
						</div>
					{elseif $type=="file"}
                        <div class="input" 
							data-language="{$language->code|escape}"
							{if $smarty.foreach.i18nInputs.iteration!=1}style="display:none"{/if}
						>
                        {if $name && $objectProperty}
                            {assign var=fileObject value=$item->getI18nValue($objectProperty,$language->code)}
                            {if $fileObject instanceof leafFile}
                            {assign var=previewLink value=$fileObject->getFullUrl()}
                            {/if}
                        {/if}
                        {leafFileInput accept=$accept name=$i18nName id="`$i18nName``$namespace`" file=$value previewLink=$previewLink}{/leafFileInput}
                        </div>
					{elseif $type=="objectlink"}
                        <div class="input"
							data-language="{$language->code|escape}"
							{if $smarty.foreach.i18nInputs.iteration!=1}style="display:none"{/if}
						>
                        {input type=objectlink value=$value name=$i18nName id="`$i18nName``$namespace`"}
                        </div>
                    {else}
						<input
							type="{if $type}{$type|escape}{else}text{/if}" 
							name="{$i18nName|escape}"
							id="{$i18nName|escape}{$namespace}"
							class="input {$className}"
							value="{if isset($value)}{$value|escape}{else}{$item->$i18nName|escape}{/if}"
							data-language="{$language->code|escape}"
							{if $readonly}readonly="readonly"{/if}
							{if $disabled}disabled="disabled"{/if}
							{if isset($autocomplete) && $autocomplete==false}autocomplete="off"{/if}
							{if $smarty.foreach.i18nInputs.iteration!=1}style="display:none"{/if}
						/>
					{/if}
					
				{/foreach}	
			</div>
			{strip}
			{if count($languageList) > 1}
				<div class="languageWrap">
					{foreach from=$languageList item=language name=i18nLanguages}
						<button type="button" data-language="{$language->code|escape}" class="noStyling {if $smarty.foreach.i18nLanguages.iteration==1}active{/if}">
							{$language->code|escape}
						</button>
					{/foreach}
				</div>
			{/if}				
			{/strip}
		</div>
		{if $postAlias}<span class="post">{alias code=$postAlias}</span>{/if}
		{if $post}<span class="post">{$post|escape}</span>{/if}
	</div>
	<div class="clear"></div>
</div>




