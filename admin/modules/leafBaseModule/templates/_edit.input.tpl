<div class="field {$fieldWrapClass} {if $type=="checkbox"}checkboxFieldWrap{/if}">
	<div class="labelWrap">
		{if $type == "checkbox"}
			<span class="label">&nbsp;</span>
		{else}
			<label for="{$name|escape}{$namespace}">
				{assign var=aliasString value=$name}
				{if $alias}
					{assign var=aliasString value=$alias}
				{/if}
				{if $aliasContext}
					{alias code=$aliasString context=$aliasContext}:
				{else}
					{alias code=$aliasString}:{if $required} *{/if}
				{/if}
			</label>
			{if $descriptionAlias}
				<div class="description">
					{alias code=$descriptionAlias}
				</div>
			{/if}
		{/if}
	</div>
	<div class="value">
		{if $type == "date"}
			{if !$value}
				{assign var=value value=$item->$name}
			{/if}
			{if $value=="0000-00-00"}
				{assign var=value value=""}
			{/if}
			{input type=date name=$name value=$value id="`$name``$namespace`" useNormalized=true}
		{else}
            {if $type == "leafFile"}
                {leafFileInput accept=$accept name=$name id="`$name``$namespace`" file=$value|default:$item->$name previewLink=$previewLink}{/leafFileInput}
            {else}
			<input 
				type="{if $type}{$type}{else}text{/if}" 
				name="{$name|escape}" id="{$name|escape}{$namespace}" class="{$className}"
				value="{if isset($value)}{$value|escape}{elseif $type=='checkbox'}1{else}{$item->$name|escape}{/if}" 
				{if $multiple}multiple="true"{/if}
				{if $readonly}readonly="readonly"{/if}
				{if $disabled}disabled="disabled"{/if}
				{if isset($autocomplete) && $autocomplete==false}autocomplete="off"{/if}
				{if $type=="checkbox" && ($item->$name || $checked)}checked="checked"{/if}
			/>
            {/if}
			{if $postAlias}<span class="post">{alias code=$postAlias}</span>{/if}
			{if $post}<span class="post">{$post|escape}</span>{/if}
            {if $postHtml}<span class="post">{$postHtml}</span>{/if}
			{if $type == "checkbox"}
				<label for="{$name|escape}{$namespace}" class="checkboxLabel">
					{if $alias}
						{alias code=$alias}
					{else}
						{alias code=$name}
					{/if}
				</label>
				{if $descriptionAlias}
					<div class="description">
						{alias code=$descriptionAlias}
					</div>
				{/if}
			{/if}
		{/if}
	</div>
	<div class="clear"></div>
</div>
