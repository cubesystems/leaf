<ul id="childs{$root}">
{foreach from=$list item=object}
	<li id="objectli{$object.id}">
   		{if $object.group_image}
		<a href="{$object.id}" onclick="return get_childs(this)" class="objectTreeNodeOpen" id="objectNodeImg{$object.id}"><!-- --></a>{else}<img src="images/px.gif" width="9" height="9" alt="" />
		{/if}
		<a href="{$object.id}" onclick="return pickObject(this, '{$object.file_name}')" name="{$object.id}" id="object_{$object.id}"  class="{if $object.id==$group_id}activeObject{/if} {if !$object.visible}hidden{/if}"><img src="{$object.icon_path}" class="noIEPngFix" alt="" /> {$object.name}</a>
	</li>
{/foreach}
</ul>
