{alias_context code="admin:content"}

<div class="panelLayout module-content method-edit">
	{strip}
		<div class="secondaryPanel">
			<div id="objectsSidebar">
				{strip}
					<ul id="objectTools" class="block actionsToolbar">
						<li>
							<button type="button" onclick="delete_confirm()">
								<span>{alias code=delete}</span>
							</button>
                            <input type="hidden" class="messageCannotDeleteProtected" value="{alias code=cannotDeleteProtected}" />
						</li>
						<li>
							<button type="button" onclick="move_confirm()">
								<span>{alias code=move}</span>
							</button>
						</li>
						<li class="last">
							<button type="button" onclick="copy_confirm()">
								<span>{alias code=copy}</span>
							</button>
						</li>
					</ul>
				{/strip}
				<div class="objectsTreeWrap">
					<div id="objectsTree" class="objectsTree">
						<form id="objectForm" action="index.php?module=content&amp;do=" method="post">
							{$objects_tree}
						</form>
					</div>
				</div>

			</div>
		</div>
	{/strip}
	<div class="primaryPanel">
		<div class="header">
			<div class="padding">
				<h2>
					{if $_module->active_object.name}
						{$_module->active_object.name|escape}
					{/if}
				</h2>
				{*<button style="float:right; margin-top: -2px;" onclick="jQuery(body).toggleClass('fullscreen');this.blur();">toggle fullscreen</button>*}
			</div>
		</div>


		<div id="contentModule">
			{if $objectModules}
				<ul id="leafObjectModules">
					{foreach from = $objectModules item = module }
						<li>
                            <a href="?module=content&amp;object_module={$module.module_name|escape}&amp;object_id={$smarty.get.object_id|escape}" class="button iconAndText" style="margin-left:50px;">
								<img src="modules/{$module.module_name|escape}/button-icon.png" alt="" />
								{alias code=$module.module_name context=admin:moduleNames}
                            </a>
						</li>
					{/foreach}
				</ul>
			{/if}
            {if !$_module->active_object}
                {include file="search.tpl"}
            {/if}
			{$content}
		</div>


	</div>
	{* start of old bad code :/  *}
	<div id="objectPanel"></div>
	<div id="loading">
		<img src="modules/content/img/loading.gif" alt="" />
	</div>
	{* end of old bad code :) *}
</div>

