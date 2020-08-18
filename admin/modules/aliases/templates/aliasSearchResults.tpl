{alias_context code="admin:aliases"}
{strip}
	{if count($aliases) > 0}
		<div class="sectionTitle">
			{alias code=aliases}
		</div>
		<ul class="block aliases">
			{foreach from=$aliases item=item key=key name=aliases}
				<li id="translation_search_result_{$key|escape}" {if $smarty.get.highlight && $smarty.foreach.aliases.iteration==$smarty.get.highlight}class="selected active"{/if}>
					<a href="{$_module->header_string|escape}&amp;do=edit&amp;id={$item.group_id|escape}#translation_{$key|escape}">
						<span class="name" title="{$item.nameNormal|escape}{if $item.translation} ({$item.translationNormal|escape}){/if}">
							{$item.name|escape}{if $item.translation|escape}: &quot;{$item.translation|escape}&quot;{/if}
						</span>
						<span class="context" title="{$item.groupName|escape}">
							{if empty($item.context)}
								<em>{alias code=noContext}</em>
							{else}
								{$item.context|escape}
							{/if}
						</span>
					</a>
				</li>
			{/foreach}
			{if $limit !== false && $count > $limit}
				<li class="showAll" data-alias="{alias code=showAllAliases}">
					<div class="a">
						{alias code=showAllAliases var_no=$count-$limit}
					</div>
				</li>
			{/if}
		</ul>
	{/if}
{/strip}