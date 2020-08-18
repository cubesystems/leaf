{if !isset( $value )}
	{assign var=value value=$item->$name}
{/if}
{if !empty($value) || empty($hideOnEmpty)}
	<div class="field noLabelWrapPadding {if $nl2br || $indentValue}indentValue{/if} {$fieldWrapClass}">
		<div class="labelWrap">
			<div class="label">
				{assign var=aliasString value=$name}
				{if $alias}
					{assign var=aliasString value=$alias}
				{/if}
				{if $aliasContext}
					{alias code=$aliasString context=$aliasContext}:
				{else}
					{alias code=$aliasString}:
				{/if}
			</div>
			{if $description}
				<div class="description">
					{$description}
				</div>
			{/if}
			{if $descriptionAlias}
				<div class="description">
					{alias code=$descriptionAlias}
				</div>
			{/if}
		</div>
		<div class="value">
			{if !$value && $message}
				<em>
					{if $aliasContext}
						{alias code="message:`$message`" context=$aliasContext}
					{else}
						{alias code="message:`$message`"}
					{/if}
				</em>
			{else}
                {if $type=="boolean"}
                    {if $value}
                        {assign var=value value="yes"}
                    {else}
                        {assign var=value value="no"}
                    {/if}
                    {assign var=translateValue value=1}
                {/if}   
				{if $url}
					<a href="{$url|escape}">
						{$value|escape}
						{if $postAlias}{alias code=$postAlias}{/if}
						{if $post}{$post}{/if}
					</a>
				{else}
					{if $translateValue}
                        {if $aliasContext}
                            {alias code="`$prefix``$value`" context=$aliasContext}
                        {else}
                            {alias code="`$prefix``$value`"}                                
                        {/if}
					{elseif empty($value) && $replaceEmptyWithDash == true}
						-
					{elseif empty($value) && isset($onEmpty)}
						{$onEmpty}
					{elseif $nl2br}
						{$value|escape|nl2br}
					{elseif $type=='enum'}
						{if !isset($separator)}
							{assign var=separator value="-"}
						{/if}
                        {if $aliasContext}
                            {alias code="`$name``$separator``$value`"  context=$aliasContext}
                        {else}
                            {alias code="`$name``$separator``$value`"}                            
                        {/if}
					{elseif isset($escape) && $escape == false}
						{$value}
					{else}
                        {if $type=="email"}<a href="mailto:{$value|escape}">{/if}
						{$value|escape}
                        {if $type=="email"}</a>{/if}
					{/if}
					
					{if $postAlias}{alias code=$postAlias}{/if}
					{if $post}{$post}{/if}
				{/if}
			{/if}
		</div>
		<div class="clear"></div>
	</div>
{/if}