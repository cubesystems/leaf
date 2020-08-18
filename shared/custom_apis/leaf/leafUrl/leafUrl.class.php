<?

class leafUrl
{

    const QUERY_SEPARATOR     = '?';
    const ARG_SEPARATOR       = '&';
    const KEY_VALUE_SEPARATOR = '=';
    const ARRAY_ARG_ENDING    = '[]';


    function __construct($url = null, $query = null)
    {

    }

    /*
    function getUrl()
    {
        if (empty($this->url))
        {
            $url = $this->getCurrentUrl();
            $this->setUrl( $url );
        }
        return $this->url;
    }
    */


    public static function parseUrl( $url )
    {
        // splits url in 2 parts - path & query
        // returns array

        // :WARNING: ignores username, password and fragment (after the #)

        if (
            (!is_string($url))
            ||
            (empty($url))
        )
        {
            return null;
        }

        $parsed = @parse_url( $url ); // parse_url may emit E_WARNING on bad urls
        if (
            (!$parsed)
            ||
            (empty($parsed['scheme']))
            ||
            (empty($parsed['host']))
            ||
            (empty($parsed['path']))
        )
        {
            return null;
        }

        $urlParts = array();

        // store path
        $pathPart = $parsed['scheme'] . '://' . $parsed['host'];
        if (!empty($parsed['port']))
        {
            $pathPart .= ':' . $parsed['port'];
        }
        $pathPart .= $parsed['path'];
        $urlParts['path'] = $pathPart;

        // store query
        $urlParts['query'] = (empty($parsed['query'])) ? null : $parsed['query'];

        return $urlParts;
    }

    public static function parseQueryString( $query )
    {
        // parses query string ("foo=123&bar[]=11&bar=12") into an associative array
        // returns array or null on error
        if (empty($query))
        {
            return array();
        }

        // empty values are set to null
        if (!is_string($query))
        {
            return null;
        }
        $queryParts = explode(self::ARG_SEPARATOR, $query);
        if (!is_array($queryParts))
        {
            return null;
        }

        $params = array();

        foreach ($queryParts as $queryPart)
        {
            // split each arg into key and value
            $keyValuePair = explode(self::KEY_VALUE_SEPARATOR, $queryPart);
            if (!is_array($keyValuePair))
            {
                continue;
            }
            $pairPartsCount = count($keyValuePair);
            if ($pairPartsCount < 1)
            {
                continue; // skip empty
            }
            else
            {
                $paramKey = current($keyValuePair);
                if ($paramKey == '')
                {
                    continue;
                }
                if ($pairPartsCount == 1)
                {
                    $paramValue = null;   // on empty vars (?search) set value to null
                }
                elseif ($pairPartsCount == 2)
                {
                    next($keyValuePair);
                    $paramValue = current($keyValuePair);  // on normal vars, (?search=yes) set value to .. well, value :)
                }
                else
                {
                    // on rare freaky vars (?search=yes=please) take first keyword as key, the rest as value
                    $keyKey = key($keyValuePair);
                    unset($keyValuePair[$keyKey]); // remove the key from array before joining the rest bac together
                    $paramValue = implode(self::KEY_VALUE_SEPARATOR, $keyValuePair);
                }
            }

            $paramKey = urldecode($paramKey);
            if (is_string($paramValue))
            {
                $paramValue = urldecode($paramValue);
            }

            // debug ($paramKey, 0);
            // debug ($paramValue, 0);

            // check for special case - array args

            $arrayArgEndingLength = strlen(self::ARRAY_ARG_ENDING);
            $isArray = (substr($paramKey, $arrayArgEndingLength * -1) == self::ARRAY_ARG_ENDING);

            if ($isArray)
            {
                $paramKey = substr($paramKey, 0, $arrayArgEndingLength * -1);

                // if the param is an array, check if its key already exists
                if (array_key_exists($paramKey, $params))
                {
                    // if tke key exists, check if the value is already an array
                    if (is_array($params[$paramKey]))
                    {
                        // if it is, append new value
                        $params[$paramKey][] = $paramValue;
                    }
                    else
                    {
                        // if it is not, make it an array (desotrying any existing scalar value)
                        $params[$paramKey] = array( $paramValue );
                    }
                }
                else
                {
                    // if the key does not exist, create array with the value
                    $params[$paramKey] = array( $paramValue );
                }
            }
            else
            {
                // if the param is not an array, simply set the value
                $params[$paramKey] = $paramValue;
            }
            // debug($isArray, 0);

        }

        // with identical urls as inputs, $params and $_GET (unmodified) should be identical
        // debug ($params, 0);
        // debug ($_GET);

        return $params;
    }


    public static function buildQueryString( $params )
    {
        // convert params to urlencoded strings of key/value pairs (flatten array params)

        // debug ($params);
        $strings = array();
        foreach ($params as $key => $value)
        {
            $key = urlencode($key);
            if (is_array($value))
            {
                $key .= self::ARRAY_ARG_ENDING;
                foreach ($value as $arrayValue)
                {
                    $item = $key . self::KEY_VALUE_SEPARATOR . rawurlencode($arrayValue);
                    $strings[] = $item;
                }
            }
            else
            {
                if (is_null($value))
                {
                    $item = $key;
                }
                else
                {
                    $item = $key . self::KEY_VALUE_SEPARATOR . rawurlencode($value);
                }
                $strings[] = $item;
            }
        }
        // debug ($strings);

        $queryString = implode( self::ARG_SEPARATOR , $strings);
        return $queryString;
    }

    public static function buildUrl( $path, $query = null)
    {
        $url = $path;
        if (!empty($query))
        {
            if (is_array($query))
            {
                $query = self::buildQueryString( $query );
            }
            $url .= self::QUERY_SEPARATOR . $query;
        }
        return $url;

    }
}

?>