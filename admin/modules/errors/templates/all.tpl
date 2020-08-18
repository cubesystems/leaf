<div class="errors">
	<form action="{$smarty.const.WWW}" class="search-form field">
        {foreach from=$_module->getModuleUrlParts('search', 'page') key=varKey item=varValue}
            <input type="hidden" name="{$varKey|escape}" value="{$varValue|escape}" />
        {/foreach}
		<input type="text" name="search" id="search" value="{$smarty.get.search|escape}" tabindex="1" />
		<button type="submit">{alias code="search"}</button>
	</form>
    {simpleForm confirmationContext=$_module->aliasContext buttonAliasContext=$_module->aliasContext do=deleteAll type="delete_all"}{/simpleForm}
	
	<table class="leafTable" cellpadding="0" cellspacing="0">
		<thead>
			<tr>
				<th class="edit">&nbsp;</th>
				<th>{alias code=occurrences}</th>
				<th>{alias code=level}</th>
				<th class="edit">&nbsp;</th>
				<th class="date">{alias code=date}</th>
				<th>{alias code=user_ip}</th>
				<th class="last">{alias code=message}</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			{if count($collection) > 0}
				{foreach from=$collection item=item name=name}
					<tr {if $smarty.foreach.name.iteration%2==0}class="alternate"{/if}>
						<td class="tree">
							<button class="noStyling expandTool hash-{$item->getMessageHash()}" type="button"/>
						</td>
						<td>
							{$item->count}
						</td>
						<td>
							{$item->getLevelName()}
						</td>
						<td class="edit url">
							<ul class="block">
								<li>
									<a href="{$_module->getModuleUrl()|escape}&amp;do=view&amp;id={$item->id}">
										<img src="images/icons/page_white_text.png" alt="" />
									</a>
								</li>
							</ul>
						</td>
						<td class="date">
							<ul class="block">
								<li>
									{" "|str_replace:"&nbsp;":$item->add_date}
								</li>
							</ul>
						</td>
						<td class="ip">
							<ul class="block">
								<li>
									{$item->user_ip}{if !empty($item->user_forwarded_ip)} ({$item->user_forwarded_ip|escape}){/if}
								</li>
							</ul>
						</td>
						<td class="last">
							{$item->message} in "{$item->file}" on line {$item->line}.
						</td>
						<td>
						{simpleForm module="errors" do="deleteError" id=$item->getMessageHash() button="images/icons/bin_empty.png"}{/simpleForm}
						</td>
					</tr>
				{/foreach}
			{else}
				<tr>
					<td colspan="7">{alias code="nothing_found"}</td>
				</tr>
			{/if}
		</tbody>
		
	</table>
	{include file="_page_navigation.tpl"}
</div>

