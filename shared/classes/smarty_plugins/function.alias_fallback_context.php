<?php

// sets default alias context fallback for current template

function smarty_function_alias_fallback_context($params, & $smarty)
{
	require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
    // validate params
    // 'code' must be set to string
    if (
        (!isset($params['code']))
    )
    {
        return 'missing code argument for alias_fallback_context.';
    }
    elseif (!is_string($params['code']))
    {
        return 'bad code argument for alias_fallback_context.';
    }
    $code = $params['code'];

    alias_cache::setFallbackContext($smarty, $code);

    return null;
}

?>
