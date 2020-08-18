<div class="thead">
	<div>
		<span>{alias code=name}</span>
		<span>{alias code=default_module}</span>
		<span>{alias code=users}</span>
	</div>
</div>
<div class="tbody">
	{foreach from=$collection item=item name=name}
		<a href="{include file=$_module->pathTo('all._url')}">
			<span>{$item->name|escape}</span>
			<span>{$item->default_module|escape}</span>
			<span>{$item->getUsersCount()|escape}</span>
		</a>
	{/foreach}
</div>
