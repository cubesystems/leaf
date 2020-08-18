{if !$node_get}
	{if $field.properties.count}({$field.properties.count|escape}){/if}
	<ul class="block array" id="{$field.input_id|escape}">
	{assign var = "arrayId" value = $field.input_id}
{/if}
{if $field.value}
	{foreach from = $field.value item=array_item name = array_items}
		<li class="arrayItem itemNr{if $node_get_nr}{$node_get_nr|escape}{else}{$smarty.foreach.array_items.iteration}{/if}">
			<div class="arrayItemH">
				<a class="delHref" href="#" onclick="item_delete_href(this); return false;">
					<img src="images/icons/bin_empty.png" alt="delete" />
				</a>
				<span class="drag"><img src="images/sortIcon.gif" alt="" /></span>
			</div>
			{foreach from=$array_item item=array_item}
				{assign var = "field" value = $array_item}
				<div class="templateField {$field.type|escape}Field">
					{if $field.type != 'hidden'}
						<span>
							<label for="{$field.input_id|escape}" class="fieldName">
								{if $field.properties.description} 
									{$field.properties.description|escape}
								{else}
									{$field.name|escape}
								{/if}
							</label>
						</span>
					{/if}
					{include file = "types/`$field.type`.tpl"}
				</div>
			{/foreach}
		</li>
	{/foreach}
{/if}
{if !$node_get}
	</ul>
{/if}