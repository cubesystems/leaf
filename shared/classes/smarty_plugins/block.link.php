<?php
/*
    available arguments:
        object -- object id OR an open object (extending leaf_object_module)
        link   -- object id OR url string. http:// will be appended to start if needed
        url    -- full url, no processing will be done except html escaping
        query    -- GET query parameters added to end of url
        fragment -- fragment identifier for url

        rel    -- rel attribute for <a>
        class  -- class attribute for <a>
        target -- target attribute for <a>

    examples:
        {link object=$object}{/link} ==> <a href="{$object->object_data.id|orp|escape}">{$object->object_data.name|escape}</a>
        {link object=3} foo bar baz {/link} ==> <a href="{3|orp|escape}"> foo bar baz </a>
        {link object=3 fragment="comments"} foo bar baz {/link} ==> <a href="{3|orp|escape}#{fragment|escape}"> foo bar baz </a>
        {link object=3 query="color=black&amount=2"} foo bar baz {/link} ==> <a href="{3|orp|escape}?{query|escape}"> foo bar baz </a>

        link overrides object for href:
        {link object=$object link=12}{/link} ==> <a href="{12|orp|escape}">{$object->object_data.name|escape}</a>

        url overrides both link and object for href:
        {link object=$object link=12 url="foo"}{/link} ==> <a href="foo">{$object->object_data.name|escape}</a>

*/

function smarty_block_link (array $params, $content, &$smarty, &$repeat)
{
    if (is_null($content)) // opening tag
    {
        return;
    }

    $output = '<a';

    // add href
    $url = '';
    $objectGiven = false;
    if (!empty($params['object']))
    {
        if (isPositiveInt( $params['object'] ))
        {
            if (strlen($content) == 0)
            {
                 $params['object'] = _core_load_object($params['object']);
            }
            else
            {
                $url = orp($params['object']);
            }
        }

        if
        (
            ($params['object'] instanceof leaf_object_module)
            &&
            (!empty($params['object']->object_data['id']))
        )
        {
            $objectGiven = true;
            $url = orp($params['object']->object_data['id']);
        }
    }

    // link override with link
    if (!empty($params['link']))
    {
        if (isPositiveInt( $params['link'] ))
        {
            $url = orp($params['link']);
        }
        else
        {
            $url = $params['link'];
        }

        if (strpos($url,'://') === false)
        {
            $url = 'http://' . $url;
        }
    }

    // link override with url
    if (!empty($params['url']))
    {
        $url = $params['url'];
    }

	// add query
    if (!empty($params['query']))
    {
        $url .= '?' . htmlspecialchars($params['query']);
    }

	// add fragment
    if (!empty($params['fragment']))
    {
        $url .= '#' . htmlspecialchars($params['fragment']);
    }

    $output .= ' href="' . htmlspecialchars($url) . '"';

    // add class
    if (!empty($params['class']))
    {
        $output .= ' class="' . htmlspecialchars($params['class']) . '"';
    }

    // add rel
    if (!empty($params['rel']))
    {
        $output .= ' rel="' . htmlspecialchars($params['rel']) . '"';
    }

    // add hreflang
    if (!empty($params['hreflang']))
    {
        $output .= ' hreflang="' . htmlspecialchars($params['hreflang']) . '"';
    }

    // add target
    if (!empty($params['target']))
    {
        $output .= ' target="' . htmlspecialchars($params['target']) . '"';
    }

    // insert object name in content if content is empty and an object is given
    if (
        (strlen($content) == 0)
        &&
        ($objectGiven)
    )
    {
        $content = htmlspecialchars($params['object']->object_data['name']);
    }

    $output .= '>' . $content . '</a>';

    return $output;

}

?>
