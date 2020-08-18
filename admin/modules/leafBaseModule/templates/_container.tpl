{assign var=submenuGroupName value=$_module->getSubmenuGroupName()}
{alias_context code="admin:leafBaseModule:`$submenuGroupName`"} {* TODO: refactor this *}
<div class="panelLayout module-{$_module|get_class} method-{$_module->getCurrentOutputMethod()}">
	{strip}
		<div class="secondaryPanel">
			<div class="inner standardizedSubmenu">
				<ul class="block">
					{foreach from=$_module->getSubMenu() item=menuSection key=sectionTitle}
                        {if $_module->hasEnabledItems($sectionTitle)}
                            {assign var=cookieName value="submenu:`$sectionTitle`"}
                            <li {if $smarty.cookies.$cookieName}class="collapsed"{/if}>
                                <div class="sectionTitle" data-title="{$sectionTitle}">
                                    {alias code=$sectionTitle}
                                </div>
                                <ul>
                                    {foreach from=$menuSection item=item key=title}
                                        <li class="{if $_module->isActiveMenuItem($title)}active{/if} {if $_module->isDisabledMenuItem($title)}disabled{/if}">
                                            {if is_array($item)}
                                                {if !$item.disabled}
                                                    <a href="{$item.url|escape}" >
                                                        {alias code=$title} 
                                                        {if $item.badgeHtml}
                                                            {$item.badgeHtml}
                                                        {/if}
                                                    </a>
                                                {/if}
                                            {else}
                                                <a href="{$item|escape}" >
                                                    {alias code=$title}
                                                </a>
                                            {/if}
                                        </li>
                                    {/foreach}
                                </ul>
                            </li>
                        {/if}
					{/foreach}
				</ul>
			</div>
		</div>
	{/strip}
    {assign var=message value=$_module->getMessage()}
	<div class="primaryPanel {if !is_null($message)}hasMessage{/if}">
        {if $message}
            <ul class="block leafMessages">
                <li class="leafMessage {$message.level}">
                    {if $message.aliasCode}
                        {alias code=$message.aliasCode}
                    {/if}
                </li>
            </ul>
        {/if}
		{$content}
	</div>
</div>
<div class="webkitTestBlock"></div>
