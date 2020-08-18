{strip}
    <input type="hidden" class="messageAccessDeniedTreeClick" value="{alias code=accessDeniedTreeClick context=admin:objectAccess}" />

	<ul id="childs{$root_id|escape}">
		{defun name="recursion" list=$objects}
			{assign var="name" value=$list.0.id}
			{foreach from=$list item=object name=$name}
				<li id="objectli{$object.id|escape}" class="{if !$object.allowed}notAllowed{else}allowed{/if}">
					<div class="objectLiDiv template-{$object.template|stringtolatin:true:true|escape} {if $object.id == $smarty.get.object_id}activeNodeWrap{/if}">
						{if !$dialog && $object.allowed}
							<a class="objectPanelHref" onclick="return start_panel(this, {$object.id|escape:javascript|escape})" href="?module=content&amp;do=get_context_menu&amp;group_id={$object.id|escape}">
								<img src="modules/content/img/add.png" alt="" />
							</a>
						{/if}
						{if !$dialog}
							<label class="objectCheck{if !$object.allowed} objectCheckDisabled{/if}">
								<input {if !$object.allowed || !$object.allDescendantsAllowed}disabled="disabled" {if !$object.allowed} title="{alias code=operationsNotPermitted context=admin:objectAccess}" {elseif !$object.allDescendantsAllowed} title="{alias code=operationsNotPermitted context=admin:objectAccess}"{/if}{/if} type="checkbox" name="objects[]" value="{$object.id|escape}" />
							</label>
						{/if}
						{if $object.group_image}
							<button class="toggleChildren groupBox groupBox{if $object.group_image == 'open'}Open{else}Close{/if}" type="button" data-id="{$object.id|escape}" {if $dialog}data-dialog="{$dialog|escape}"{/if}></button>
						{else}
							<span class="groupBox"><!-- --></span>
						{/if}
						<span class="objectHref">
						    {if $dialog}
						      {assign var=href value="#"}
						    {else}
						      {assign var=href value="?module=content&do=edit_object&object_id=`$object.id`"}
						    {/if}
							<a href="{$href|escape}" {if !$object.allowed}onclick="alert(jQuery('input.messageAccessDeniedTreeClick').val());return false;"{elseif $dialog}onclick="{$dialog|escape}('{$object.id|escape:javascript|escape}')"{/if} id="object_{$object.id|escape}" title="{$object.name|escape:"html"}" class="{if $object.id == $smarty.get.object_id}activeObject{/if} {if !$object.visible}hidden{/if}">
								<img class="noIEPngFix {if $object.iconIsFlag}flag{/if}" src="{$object.icon_path|escape}" alt="" />
								{if !$object.allowed}
								<img class="iconOverlay" src="images/icons/notAllowed.png" alt="" />
								{/if}
								{$object.name|mb_truncate:"UTF8":22:"..."|escape:"html"}
							</a>
						</span>
					</div>
					{if $object.childs}
						<ul id="childs{$object.id|escape}">
							{fun name="recursion" list=$object.childs}
						</ul>
					{/if}
				</li>
			{/foreach}
		{/defun}
	</ul>
{/strip}