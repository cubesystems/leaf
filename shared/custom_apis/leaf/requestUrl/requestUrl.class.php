<?
require_once SHARED_PATH . 'classes/singleton/_init.php';

class requestUrl extends singleton_object
{

    public $url, $parsedUrl, $queryParams, $protocol;

    var $filterParams = array('random', 'x' , 'y');

    var $modifiers = array();


    function __construct()
    {
        $this->reset();
    }

    function setUrl($url)
    {
        if (
            (!is_string($url))
            ||
            (empty($url))
        )
        {
            return false;
        }
        $this->url = $url;

        $this->parsedUrl = null;
        return true;
    }
    
    function setProtocol($protocol)
    {
        if (
            (!is_string($protocol))
            ||
            (empty($protocol))
        )
        {
            return false;
        }
        $this->protocol = $protocol;
        return true;
    }

    function getUrl()
    {
        if (empty($this->url))
        {
            $url = $this->getCurrentUrl();
            $this->setUrl( $url );
        }
        return $this->url;
    }

    function getCurrentUrl()
    {
        if (
            (empty($_SERVER['HTTP_HOST']))
            ||
            (empty($_SERVER['REQUEST_URI']))
        )
        {
            die ('$_SERVER does not contain HTTP_HOST or REQUEST_URI');
        }
        if ($this->protocol)
        {
            $url = $this->protocol;
        }
		elseif (self::isHttpsOn())
		{
			$url ='https';
		}
		else
		{
			$url ='http';
        }

        $hostParts = explode(':', $_SERVER['HTTP_HOST']);
        if(sizeof($hostParts) == 2)
        {
            $port = '';
        }
        elseif($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443')
        {
            $port = ':'. $_SERVER['SERVER_PORT'];
        }
        else
        {
            $port = '';
        }

        $url .= '://' . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
        return $url;
    }

    public static function isHttpsOn()
    {
        return (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || $_SERVER['HTTPS'] == 'on'));
    }
    
    
    function getParsedUrl()
    {
        if (is_null($this->parsedUrl))
        {
            $url = $this->getUrl();
            if (!$url)
            {
                return null;
            }
            $parsedUrl = leafUrl::parseUrl( $url );
            $this->parsedUrl = $parsedUrl;
        }
        if (!$this->parsedUrl)
        {
            return null;
        }
        return $this->parsedUrl;
    }


    // compatibility methods for older code using this from outside
    public function parseUrl( $url )
    {
        return leafUrl::parseUrl( $url );
    }

    public function parseQueryString( $query )
    {
        return leafUrl::parseQueryString( $query );
    }



    function getParsedParams( $query )
    {
        $params = leafUrl::parseQueryString( $query );

        if (!is_array($params))
        {
            return null;
        }

        // strip out filter params
        foreach ($this->filterParams as $filterParam)
        {
            unset($params[$filterParam]);
        }
        return $params;
    }


    function addModifier($data, $type = 'add')
    {
        // adds modifier to modifier list
        // $data must be a query string (e.g. "foo=123&bar[]=123&bar[]=444&baz")
        // empty arguments

        $types = array('add', 'remove');
        if (!in_array($type, $types))
        {
            return false;
        }

        $parsedData = leafUrl::parseQueryString( $data );
        if (!is_array($parsedData))
        {
            return false;
        }
        foreach ($parsedData as $varName => $value)
        {
            $modifier = array(
                'type'     => $type,
                'variable' => $varName,
                'value'    => $value
            );
            $this->modifiers[] = $modifier;
        }

        // debug ($parsedData);

        return true;
    }

    function reset()
    {
        $this->resetModifiers();
        $this->resetUrl();
    }

    function resetModifiers()
    {
        $this->modifiers = array();
    }

    function resetUrl()
    {
        $this->url = null;
        $this->parsedUrl = null;
        $this->queryParams = null;
    }

    function getModifiedUrl()
    {
        // takes the url and applies all modifiers (add/remove params)
        // the url and modifiers must be already set

        // get url
        $urlParts = $this->getParsedUrl();
        if (!$urlParts)
        {
            return null;
        }

        // get params
        $params = $this->getParsedParams ( $urlParts['query'] );
        if (!is_array($params))
        {
            return null;
        }

        // debug ($params, 0);
        // debug ($this->modifiers, 0);

        // apply modifiers to a copy of the params
        $newParams = $params;

        foreach ($this->modifiers as $modifier)
        {

            $type    = $modifier['type'];
            $varName = $modifier['variable'];
            $value   = $modifier['value'];

            if ($type == 'add')
            {
                // if value is not set, set it
                if (!isset($newParams[$varName]))
                {
                    $newParams[$varName] = $value;
                }
                else
                {
                    // is already set. further actions depend on type of the new value
                    if (is_array($value))
                    {
                        // if the new value is an array
                        // check if the target value is already an array. overwrite with an empty array if not.
                        if (!is_array($newParams[$varName]))
                        {
                            $newParams[$varName] = array();
                        }

                        // append new value(s) (only if unique)
                        foreach ($value as $arrayValue)
                        {
                            if (!in_array($arrayValue, $newParams[$varName] ))
                            {
                                $newParams[$varName][] = $arrayValue;
                            }
                        }
                    }
                    else
                    {
                        // not an array, overwrite any existing value with
                        $newParams[$varName] = $value;
                    }
                }
            }
            elseif ($type == 'remove')
            {

                if (!array_key_exists($varName, $newParams))
                {
                    continue; // nothing to remove, continue with next
                }

                // if only variable name is given, simply unset the variable
                if (is_null($value))
                {
                    unset($newParams[$varName]);
                }
                else
                {
                    // if value given also, remove only with the matching value
                    if (is_array($value))
                    {
                        // if removable value is an array of values
                        if (!is_array($newParams[$varName]))
                        {
                            continue; // if the existing var is not an array, continue to next modifier
                        }

                        // remove all given values from the existing array
                        foreach ($value as $arrayValue)
                        {
                            $existingKeys = array_keys( $newParams[$varName], $arrayValue );
                            if (is_array($existingKeys))
                            {
                                foreach ($existingKeys as $existingKey)
                                {
                                    unset ($newParams[$varName][$existingKey]);
                                }
                            }
                        }
                    }
                    else
                    {
                        // if removable value is a scalar value,
                        // remove the variable if the current value matches the removable value
                        if ($newParams[$varName] == $value)
                        {
                            unset ( $newParams[$varName] );
                        }
                        // $currentKey = array_search( $varValue, $params[$varName] );


                    }


                }
            }

        }

        // remove any empty arrays left
        foreach ($newParams as $key => $value)
        {
            if (is_array($value) && empty($value))
            {
                unset($newParams[$key]);
            }
        }

        $newQueryString = leafUrl::buildQueryString ( $newParams );

        $modifiedUrl = $this->buildUrl( $urlParts['path'] , $newQueryString);

        return $modifiedUrl;
    }

    // compatibility methods for older code using this from outside
    public function buildQueryString( $params )
    {
        return leafUrl::buildQueryString ( $newParams );
    }

    public function buildUrl( $path, $queryString = null)
    {
        return leafUrl::buildUrl( $path, $queryString );
    }



    function getUrlWithoutParams()
    {
        $urlParts = $this->getParsedUrl();
        if (!$urlParts)
        {
            return null;
        }
        $url = $this->buildUrl($urlParts['path']);
        return $url;
    }


}

?>
