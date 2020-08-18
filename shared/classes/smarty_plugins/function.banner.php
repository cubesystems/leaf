<?php
/*
    required arguments:
        object   -- positiveint OR banner object OR leaf_object_module object
        OR
        objectId  -- positiveint
        OR
        text + textClass -- to use image_text
        OR
        alias/context/language + textClass -- to use image_text with alias
        OR
        fileUrl/width/height -- to show a banner that is not a fileobject

        {banner object = $bannerObject}
        {banner object = $contentObject}
        {banner object = 123}
        {banner objectId = 123}
        {banner text = $object.name textClass = "mainMenu"}
        {banner text = $object.name textClass = "mainMenu" textStyle="max-width: 100"}

        {banner alias = "submit" context="form" language=1 textClass = "mainMenu"}
        {banner fileUrl = "http://foo.bar/foobar.jpg" width="123" height="42"}

        optional link parameter accepts URL or object id, or leaf_object_module object
        {banner object=123 link=456}
        {banner object=123 link=http://google.lv}
        {banner object=123 link=google.lv}
        {banner object=123 link=$contentObject}

        url paremeter overrides link and can contain only the exact url
        {banner object=123 url=http://google.lv}


        object overrides text mode

*/

function smarty_function_banner($params, & $smarty)
{
    // require_once(SHARED_PATH . 'classes/stopwatch.class.php');
    // stopWatch::go();

    require_once(SHARED_PATH . 'classes/banner/_init.php');
    // check for object/objectId
    $objectId = $bannerObject = null;

    // if object param given, it may be either an actual banner object, or an id of an object
    if (isset($params['object']))
    {
        if (
            (is_object($params['object']))
            &&
            ($params['object'] instanceof banner)
        )
        {
            $bannerObject = $object;
        }
        elseif (
            (is_object($params['object']))
            &&
            ($params['object'] instanceof leaf_object_module)
        )
        {
            $objectId = (int) $params['object']->object_data['id'];
        }
        elseif (isPositiveInt($params['object']))
        {
            $objectId = (int) $params['object'];
        }
    }

    if (isset( $params['pixelRatio'] ))
    {
        $pixelRatio = $params['pixelRatio'];
    }
    else
    {
        $pixelRatioConfig = leaf_get( 'properties', 'textBanner', 'pixelRatio' );
        if (!empty( $pixelRatioConfig ))
        {
            $pixelRatio = $pixelRatioConfig;
        }
        else
        {
            $pixelRatio = 1.0;
        }

        $params['pixelRatio'] = $pixelRatio;
    }

    // if objectId param given it overrides object param.
    // may contain only an id of an object
    if (
        (isset($params['objectId']))
        &&
        (isPositiveInt($params['objectId']))
    )
    {
        $objectId = (int) $params['objectId'];
    }


    //
    if (
        (isset($params['fileUrl']))
        &&
        (isset($params['width']))
        &&
        (isset($params['height']))
    )
    {
        $bannerObject = new banner;
        if (!$bannerObject->loadFromUrl( $params['fileUrl'], $params['width'], $params['height'] ))
        {
            return null;
        }
    }



    // if no object found so far, check for text / textClass
    $textMode = false;
    $text = $textClass = $textStyle = null;
    if ((!$objectId) && (!$bannerObject))
    {
        // check if both of the required text params are given
        if (
            (isset($params['text']))  // can't use empty(), text may theoretically be an empty string
            &&
            (!empty($params['textClass']))
        )
        {
            $textMode = true;
            $text      = $params['text'];
            $textClass = $params['textClass'];
        }
    }

	// check for alias
	if (
		(isset($params['alias']))  // can't use empty(), text may theoretically be an empty string
	)
	{
		$alias = $params['alias'];

		// check for alias context
		if(isset($params['context']))
		{
			$context = $params['context'];
		}
		else
		{
			$context = alias_cache::getContext($smarty);
		}

		// check for alias language
		if(isset($params['language']))
		{
			$language = $params['language'];
		}
		else
		{
			$language = null;
		}

		// check for alias fallback context
		if(isset($params['fallbackContext']))
		{
			$fallbackContext = $params['fallbackContext'];
		}
		else
		{
			$fallbackContext = null;
		}

		// check for alias enableTags
		if(isset($params['enableTags']))
		{
			$enableTags = $params['enableTags'];
		}
		else
		{
			$enableTags = null;
		}

		// check for alias variables
		$variables = array();
		foreach( $params as $name => $value )
		{
			if( strpos( $name, 'var_' ) === 0 )
			{
				$variables[ substr( $name, 4 ) ] = $value;
				unset( $params[ $name ] );
			}
		}
		$params['variables'] = $variables;

		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
		$aliasText = alias_cache::getAlias($alias, $context, false, $language, $fallbackContext, $variables, $enableTags);

	    // check for text mode
	    if (!$objectId && !$bannerObject && !empty($params['textClass']))
	    {
			$textMode = true;
			$text = $aliasText;
			$textClass = $params['textClass'];
	    }
		elseif(empty($params['alt']))
		{
			$params['alt'] = $aliasText;
		}
		$params['text'] = $text; // re-add to params for cache key
	}

	if ($textMode)
	{
        $textStyle = (!empty($params['textStyle'])) ? $params['textStyle'] : null;
	}


    if ((!$objectId) && (!$bannerObject) && (!$textMode))
    {
        // something went wrong
        return null;
    }


    // load banner object if necessary

    if ((!$bannerObject) && ($objectId))  // id given, object not yet loaded, load it
    {
        $bannerObject = new banner;
        if (!$bannerObject->loadFromObject($objectId))
        {
            return null;
        }

    }
    elseif ($textMode) // no object/id given, load from text
    {
        $bannerObject = new banner;
        if (!$bannerObject->loadFromText($text, $textClass, $pixelRatio, $textStyle))
        {
            return null;
        }
    }

    if (isset($params['url']))
    {
        $bannerObject->setUrl($params['url']);
    }
    elseif (isset($params['link']))
    {
        $bannerObject->setLink($params['link']);
    }

    // if (isset($p))
    // debug ($bannerObject);

    // debug ($params, 0);
    $cacheKey = $bannerObject->getCacheKey( $params );

    if (!is_null($cacheKey))
    {
        $cachedHtml = $bannerObject->getCachedHtml( $cacheKey );
        if (!is_null($cachedHtml))
        {
            // debug( 'cache!', 0);
            // stopWatch::end();
            return $cachedHtml;
        }
    }


    // clear used params
    unset ($params['objectId'], $params['object'], $params['link'], $params['text'], $params['textClass'], $params['textStyle'], $params['fileUrl']);

    // forward all other params to output method
    $output = $bannerObject->getHtml($params);

    $bannerObject->cacheHtml($cacheKey, $output);

    // stopWatch::end();
    return $output;
}
?>
