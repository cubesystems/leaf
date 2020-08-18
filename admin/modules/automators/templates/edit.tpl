<form class="validatable edit" method="post" action="{$_module->getModuleUrl()}&amp;do=save&amp;id={$item->id}" enctype="multipart/form-data">
	{include file=$_module->pathTo('_edit.header')}
	<div class="content">
		<div class="section">
            {include file=$_module->pathTo('_edit.input') fileName='_edit.input' name=title}
			{if $item->isActive || !$item->id}{assign var=isActiveChecked value=true}{/if}
			{include file=$_module->pathTo('_edit.input') name=isActive type="checkbox" checked=$isActiveChecked}
		</div>
		<div class="section list" data-type="triggers">
            <h3>{alias code=triggers} <img src="images/icons/add.png" class="addIcon" /></h3>
            {foreach from=$item->getTriggers(true) item=trigger}
                {include file=$_module->pathTo('_trigger') item=$trigger}
            {/foreach}
		</div>
		<div class="section list" data-type="actions">
            <h3>{alias code=actions} <img src="images/icons/add.png" class="addIcon" /></h3>
            {foreach from=$item->getActions(true) item=action}
                {include file=$_module->pathTo('_action') item=$action}
            {/foreach}
		</div>
	</div>
	{include file=$_module->pathTo('_edit.footer')}
</form>
