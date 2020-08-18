This is leafBaseModule's _rows.tpl file - please copy it to your module and fill with the correct content

{*** example for tableMode:html ***}
{*
<thead>
	<tr class="unselectable">
		<th>{alias code=name}</th>
		<th>{alias code=category}</th>
		<th>{alias code=createDate}</th>
	</tr>
</thead>
<tbody>
	{if count($collection) > 0}
		{foreach from=$collection item=item name=name}
			<tr	onclick="document.location=jQuery(this).attr('data-url')" data-url="{include file=$_module->pathTo('all._url')}">
				<td>{$item->name|escape}</td>
				<td>{$item->category|escape}</td>
				<td>{$item->add_date}</td>
			</tr>
		{/foreach}
	{else}
		<tr class="unselectable">
			<td colspan="3">
				{alias code="nothingFound"}
			</td>
		</tr>
	{/if}
</tbody>
*}

{*** example for tableMode:css ***
 
  .thead and .tbody descendants can have any tag name - their "display" value is determined by
  distance to ancestor:
    .tbody > * { display: table-row }
    .tbody > * > * { display: table-cell }

*}
{*
<div class="thead">
	<div>
        <span>{alias code=name}</span>
		<span>{alias code=category}</span>
        <span>{alias code=createDate}</span>
	</div>
</div>
<div class="tbody">
	{foreach from=$collection item=item name=name}
		<a href="{include file=$_module->pathTo('all._url')}">
            <span>{$item->name|escape}</span>
            <span>{$item->category|escape}</span>
            <span>{$item->add_date|escape}</span>
		</a>
	{/foreach}
</div>
*}