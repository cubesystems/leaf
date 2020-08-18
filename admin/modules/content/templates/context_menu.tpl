{strip}
	{if $list}
		<ul>
			{foreach from=$list item=item name=list}
				<li>
					{if $item.type == "file"}
						<a style="background-image:url({$smarty.const.SHARED_WWW}objects/file/icon.gif)" href="?module=content&amp;do=edit_object&amp;_leaf_object_type=21&amp;object_id=0&amp;parent_id={$object_id}">file</a>
					{else}
						<a style="background-image:url({if $item.icon_path}{$item.icon_path}{else}{$smarty.const.SHARED_WWW}objects/xml_template/icon.png{/if})" href="?module=content&amp;do=edit_object&amp;_leaf_object_type=22&amp;object_id=0&amp;parent_id={$object_id}&amp;template={$item.id}">{$item.name}</a>
					{/if}
				</li>
				{if ($smarty.foreach.list.iteration % 5) == 0 && !$smarty.foreach.list.last}
					</ul>
					<ul>
				{/if}
			{/foreach}
		</ul>
	{/if}
{/strip}
