<?php

// unsets default alias context for current template

function smarty_function_alias_context_reset($params, & $smarty)
{
	require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
	alias_cache::setContext($smarty, null);
	return null;
}

?>
