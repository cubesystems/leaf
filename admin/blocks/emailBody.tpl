{alias_fallback_context code="admin:emails"}
{include file=$_module->useWidget('i18nInput') name=subject className=focusOnReady fieldWrapClass="textFieldContainer emailSubjectContainer"}
{include file=$_module->useWidget('i18nInput') name=plain type=textarea descriptionAlias=variables descriptionAliasVars=$variables}
{include file=$_module->useWidget('i18nInput') name=html  type=richtext descriptionAlias=variables descriptionAliasVars=$variables}
{alias_fallback_context code="admin:leafBaseModule"}
