{if empty($nameMethodName)}
	{assign var=nameMethodName value=getFullName}
{/if}
{if $aliasContext}
    {alias_context code=$aliasContext}
{/if}
<div class="field {$fieldWrapClass}">
	<div class="labelWrap">
        <label for="{$name|escape}{$namespace}">
            {strip}
                {assign var=aliasString value=$name}
                {if $alias}
                    {assign var=aliasString value=$alias}
                {/if}
                {if $aliasContext}
                    {alias code=$aliasString context=$aliasContext}
                {else}
                    {alias code=$aliasString}
                {/if}
                {if $labelPostfix} {$labelPostfix|escape}{/if}:
            {/strip}
        </label>
	</div>
	<div class="multipleItemsBlock multiple{$name|ucfirst|escape}Block value" data-name="{$name|escape}" {if isset($minItems)}data-min-items="{$minItems|escape}"{/if}>
		<div class="itemContainer">
            {if $fileName}		
                {assign var=loopIteration value="0"}
				{foreach from=$relations item=relationItem}
                    {assign var=loopIteration value="`$loopIteration+1`"}
					{include file=$fileName relation=$relationItem index=$loopIteration}
				{/foreach}
            {/if}			
		</div>
        {if $addItems !== false}        
		<div class="addItemBlock">
			{strip}
				<button class="addItemButton icon-and-text noStyling" type="button">
					<img src="images/icons/add.png" class="icon" alt="{alias code='addItem'}" />
					<span class="text">{alias code='addItem'}</span>
				</button>
			{/strip}
		</div>
        {/if}
	</div>
</div>
