<?

function smarty_function_alias($params, & $smarty)
{

	require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
    // validate params
    // 'code' must be set to string
    if (
        (!isset($params['code']))
    )
    {
        return 'missing code argument for alias.';
    }
    elseif (!is_string($params['code']))
    {
        return 'bad code argument for alias.';
    }
    $code = $params['code'];
    unset ( $params['code'] );



    // 'escape' must be either omitted, boolean or string.
    // further escape mode validation is done from alias_cache class
    if (!isset($params['escape']))
    {
        $params['escape'] = null;
    }
    elseif (
        (isset($params['escape']))
        &&
        (!is_string($params['escape']))
        &&
        (!is_bool($params['escape']))
    )
    {
        return 'bad escape argument for alias';
    }
    $escape = $params['escape'];
    unset ( $params['escape'] );

    // 'context' must be either omitted or string
    // if omitted, attempt to take context from smarty variable
    // further context validation is done from alias_cache class
    if (!isset($params['context']))
    {
        $params['context'] = alias_cache::getContext( $smarty );
    }
    elseif (!is_string($params['context']))
    {
        return 'bad context argument for alias';
    }
    $context = $params['context'];
    unset ( $params['context'] );


    if (!isset($params['fallbackContext']))
    {
        $params['fallbackContext'] = alias_cache::getFallbackContext( $smarty );
    }
    elseif (!is_string($params['fallbackContext']))
    {
        return 'bad fallbackContext argument for alias';
    }
    $fallbackContext = $params['fallbackContext'];
    unset ( $params['fallbackContext'] );



    if (!isset($params['language']))
    {
        $params['language'] = alias_cache::getLanguage( $smarty );
    }
    $language = $params['language'];
    unset( $params['language'] );



    if (!isset($params['enableTags']))
    {
        $params['enableTags'] = null;
    }
    $enableTags = $params['enableTags'];
    unset( $params['enableTags'] );


    if (!isset($params['amount']))
    {
        $params['amount'] = null;
    }
    $amount = $params['amount'];
    unset( $params['amount'] );


    $variables = array();
	if( !empty( $params['vars'] ) && is_array( $params['vars'] ) )
	{
		$variables = $params['vars'];
		unset( $params['vars'] );
	}
    if (!empty($params))
    {
        foreach ($params as $key => $val)
        {
            if (substr(strtolower($key), 0, 4) != 'var_')
            {
                continue;
            }

            $varName = trim(substr($key, 4));
            if (empty($varName))
            {
                continue;
            }

            $variables[$varName] = $val;
            unset($params[$key]);
        }
    }


    return alias_cache::registerAlias($code, $context, $escape, $language, $fallbackContext, $variables, $enableTags, $amount);
}

?>
