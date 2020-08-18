<form class="validatable edit" method="post" action="{$_module->getModuleUrl()}&amp;do=save&amp;id={$item->id}" enctype="multipart/form-data">
	{include file=$_module->pathTo('_edit.header') overrideAlias=$_module->_class_name context="admin:leafBaseModule:`$_module->submenuGroupName`"}
	{alias_fallback_context code="admin:emails"}
	<div class="content noShadow">
		<div class="section">
			{include file=$_module->useWidget('i18nInput') name=name className="focusOnReady"}
			{include file=$_module->useWidget('i18nInput') name=email}
		</div>
	</div>
	{alias_fallback_context code="admin:leafBaseModule"}
	{include file=$_module->pathTo('_edit.footer')}
</form>