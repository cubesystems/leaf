<div class="header">
	<div class="padding">
		<h2>{$item->getDisplayString()|escape}</h2>
	</div>
</div>
<div class="content view">
	<div class="section">
        {include file=$_module->pathTo('_view.field') name=login}
		{include file=$_module->pathTo('_view.field') name=name}
        {include file=$_module->pathTo('_view.field') name=surname}
        {include file=$_module->pathTo('_view.field') name=group}
        {include file=$_module->pathTo('_view.field') name=language value=$item->languageCode}
        {include file=$_module->pathTo('_view.field') name=email}
        {include file=$_module->pathTo('_view.field') name=add_date}
        {include file=$_module->pathTo('_view.field') name=last_login}
	</div>
</div>

    
{include file=$_module->pathTo('_view.footer')}
