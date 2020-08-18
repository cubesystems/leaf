<?php

// sets default alias language for current template

function smarty_function_alias_language($params, & $smarty)
{
	require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
    // validate params
    // 'code' must be set to language id
    if (
        (!isset($params['code']))
    )
    {
        return 'missing language code argument for alias_language.';
    }
    elseif (!is_scalar($params['code']) || (!$params['code']))
    {
        return 'bad language code argument for alias_language.';
    }
    $code = $params['code'];

    alias_cache::setLanguage($smarty, $code);

    return null;
}

?>
