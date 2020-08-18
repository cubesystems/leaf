<?
class banner_type
{
    var $templateDir;

    var $data;

    var $fileUrl;

    var $url;

    var $params = array();

    function banner_type()
    {
        $this->templateDir = dirname(realpath(__FILE__)) . '/templates';
    }

    function loadData( & $bannerObject )
    {
        $this->fileUrl    = $bannerObject->fileUrl;
        $this->objectId   = $bannerObject->objectId;
        $this->data       = $bannerObject->fileData;

        $this->textMode   = $bannerObject->textMode;
        $this->text       = $bannerObject->text;

        if ($bannerObject->params)
        {
            $this->params = $bannerObject->params;
        }
        return true;

    }
	
	public function setAlt($alt)
	{
		$this->params['alt'] = 	$alt;
	}

    function setUrl($url)
    {
        $this->url = $url;
    }

    function getHtml($params = array())
    {
        $class = get_class($this);

        if (strtolower(__CLASS__) == strtolower($class))
        {
            return null; // no direct calls to this class. must be called from a subclass
        }

        $objectParams = $this->params;

        $this->preProcessParams( $params );

        $combinedParams = array_merge($objectParams, $params);

        // default params
        if (!isset($combinedParams['containerTag']))
        {
            $combinedParams['containerTag'] = 'span';
        }

        if (method_exists($this, 'preProcess'))
        {
            $combinedParams = $this->preProcess( $combinedParams );
        }

        // debug ($combinedParams);
        $template = new leaf_smarty($this->templateDir);

        $template->assign('data', $this->data);
        $template->assign('url', $this->url);
        $template->assign('instance', $this);

        $template->assign($combinedParams);

        $template->assign('objectId', $this->objectId);

        $template->assign('fileUrl', $this->fileUrl);
        $templateName = $class . '.tpl';
        $output = $template->fetch($templateName);

        return $output;

    }

    function preProcessParams( & $params )
    {

        // move all var_* and param_* parameters into an array
        if (
            (!empty($params['variables']))
            &&
            (is_array($params['variables']))
        )
        {
            $variables = $params['variables'];
        }
        else
        {
            $variables = array();
        }

        if (
            (!empty($params['params']))
            &&
            (is_array($params['params']))
        )
        {
            $paramVars = $params['params'];
        }
        else
        {
            $paramVars = array();
        }
        foreach ($params as $name => $value)
        {
            $target = null;
            if (substr($name, 0, 4) == 'var_')
            {
                $name = trim(substr($name, 4));
                $target = & $variables;
            }
            elseif (substr($name, 0, 6) == 'param_')
            {
                $name = trim(substr($name, 6));
                $target = & $paramVars;
            }
            else
            {
                continue;
            }
            $target[$name] = $value;
            unset ($params[$name]);
            unset($target); // destroy reference
        }

        if ($variables)
        {
            $params['variables'] = $variables;
        }

        if ($paramVars)
        {
            $params['params'] = $paramVars;
        }

        return true;
    }

}


?>