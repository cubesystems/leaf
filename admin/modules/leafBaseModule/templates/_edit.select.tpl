<div class="field">
	<div class="labelWrap">
		<label for="{$name|escape}{$namespace}">
			{if $alias}
				{alias code=$alias}:{if $required} *{/if}
			{else}
				{alias code=$name}:{if $required} *{/if}
			{/if}
		</label>
	</div>
	<select name="{$name|escape}" id="{$name|escape}{$namespace}">
		{if $showEmptyValue !== false}
			<option value="">{if $emptyValueAlias}{alias code=$emptyValueAlias}{else}&nbsp;{/if}</option>
		{/if}
		{if $type == 'enum' || $type == 'plain'}
			{if !isset($separator)}
				{assign var=separator value="-"}
			{/if}
			{if !isset($value)}
				{assign var=value value=$item->$name}
			{/if}
			{foreach item=option key=optionKey from=$options}
				<option value="{if $type == "plain"}{$optionKey|escape}{else}{$option|escape}{/if}"{if ($type == "plain" && $optionKey == $value) || ($type == "enum" && $option == $value)} selected="selected"{/if}>
					{if isset($translateOptions) && $translateOptions == false}
						{$option|escape}
					{else}
                        {if $aliasContext}
                            {alias code="`$name``$separator``$option`" context=$aliasContext}
                        {else}
                            {alias code="`$name``$separator``$option`"}
                        {/if}
					{/if}
				</option>
			{/foreach}
		{else}
			{if !isset($value)}
				{assign var=value value=$item->$name}
			{/if}
			{foreach from=$options item=optionObject}
				<option value="{$optionObject->id}"{if $optionObject->id == $value} selected="selected"{/if}>
				    {if !$nameMethod}
						{assign var=nameMethod value=getDisplayString}
				    {/if}
	                {$optionObject->$nameMethod()|escape}
				</option>
			{/foreach}
		{/if}
	</select>
</div>
