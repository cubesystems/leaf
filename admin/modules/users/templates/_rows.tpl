<div class="thead">
	<div>
		<span>{alias code=login}</span>
		<span>{alias code=name}</span>
		<span>{alias code=surname}</span>
		<span>{alias code=group}</span>
		<span>{alias code=email}</span>
		<span>{alias code=last_login}</span>
	</div>
</div>
<div class="tbody">
	{foreach from=$collection item=item name=name}
		<a href="{include file=$_module->pathTo('all._url')}">
			<span>{$item->login|escape}</span>
			<span>{$item->name|escape}</span>
			<span>{$item->surname|escape}</span>
			<span>{$item->group|escape}</span>
			<span>{$item->email|escape}</span>
			<span>{$item->last_login|escape}</span>
		</a>
	{/foreach}
</div>
