<?php

// provides an interface to requestUrl component
// modifies the url (of the current page by default) and returns it
/*

usage examples:

    {request_url add="foo=123"}
    {request_url add="foo=123&bar=456"}
    {request_url add="foo=123" remove="bar"}
    {request_url add="foo=123" remove="bar=456"}
    {request_url add="foo[]=123&foo[]=456" remove="bar[]=789"}
    {request_url add="foo=1" escape="no"}
    {request_url url="http://www.example.com/?foo=123" add="bar=456" }
    {request_url remove_params="1"}

*/

function smarty_function_request_url($params, & $smarty)
{
    $instance = getObject( 'requestUrl' );

    $instance->reset();
    // $instance->resetModifiers();


    if (isset($params['url']))
    {
        $instance->setUrl($params['url']);
    }

    if (isset($params['protocol']))
    {
        $instance->setProtocol($params['protocol']);
    }

    if (isset($params['remove_params']))
    {
        $result = $instance->getUrlWithoutParams();
    }
    else
    {
        if (isset($params['add']))
        {
            if (is_array($params['add']))
            {
                foreach ($params['add'] as $param)
                {
                    $instance->addModifier($param, 'add');
                }
            }
            else
            {
                $instance->addModifier($params['add'], 'add');
            }
        }


        if (isset($params['remove']))
        {
            if (is_array($params['remove']))
            {
                foreach ($params['remove'] as $param)
                {
                    $instance->addModifier($param, 'remove');
                }
            }
            else
            {
                $instance->addModifier($params['remove'], 'remove');
            }
        }
        $result = $instance->getModifiedUrl();
    }
	
	if ( !empty( $params['encode'] ) && $params['encode'] == true )
	{
		$result = urlencode( $result );
	}    

    if (!isset($params['escape']))
    {
        $params['escape'] = 'html';
    }
    
    $params['escape'] = strtolower($params['escape']);

    if ($params['escape'] == 'html')
    {
        $result = htmlspecialchars($result);
    }
    
    if( !empty( $params['assign'] ) )
    {
        $smarty->assign( $params['assign'], $result );
        return;
    }
    
    return $result;
}

?>
