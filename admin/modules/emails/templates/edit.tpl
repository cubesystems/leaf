<form class="validatable edit" method="post" action="{$_module->getModuleUrl()}&amp;do=save&amp;id={$item->id}&amp;email={$_module->getMainObjectClass()}" enctype="multipart/form-data">
	{include file=$_module->pathTo('_edit.header') overrideAlias=$_module->getMainObjectClass() context="admin:leafBaseModule:`$_module->submenuGroupName`"}
	<div class="content noShadow">
		<div class="section">
			{include file=$_module->useWidget('emailBody')}
		</div>
	</div>
	{include file=$_module->pathTo('_edit.footer')}
</form>