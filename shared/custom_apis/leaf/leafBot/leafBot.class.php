<?


class leafBot extends leafBaseObject
{
    protected static $userAgentPattern = '/^LeafBot\s/u';

    protected $methods  = array('GET', 'POST');
    protected $userAgent = 'LeafBot 1.0';

    protected $maxRedirects = 20;
    protected $cookieFileName = null;

    protected $referer = null;

    function __construct()
    {
        $this->createCookieFile();
    }


    function __destruct()
    {
        $this->deleteCookieFile();
    }


    function createCookieFile()
    {
        do
        {
            $fileName = uniqid('leafBotCookies_')  .  str_replace('.', '_', rand()) . '.txt';
            $fullFileName = CACHE_PATH . $fileName;
        }
        while (file_exists( $fullFileName ));
        $this->cookieFileName = $fullFileName;
    }


    function deleteCookieFile()
    {
        if (
            (empty($this->cookieFileName))
            ||
            (!file_exists($this->cookieFileName))
            ||
            (!is_writable($this->cookieFileName))
        )
        {
            return;
        }

        @unlink ( $this->cookieFileName );
        $this->cookieFileName = null;
        return;
    }


    public function get( $url, $vars = null )
    {
        return $this->request('GET', $url, $vars);
    }


    public function post( $url, $vars = null)
    {
        return $this->request('POST', $url, $vars);
    }

    public function request( $method, $url, $vars = null )
    {
        $method = strtoupper($method);
        if ( !in_array($method, $this->methods ))
        {
            return null;
        }

        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_USERAGENT, $this->userAgent );
        curl_setopt( $curl, CURLOPT_MAXREDIRS, $this->maxRedirects );

        if(!ini_get('open_basedir') && !ini_get('safe_mode'))
        {
	        curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
        }


        if (!empty($this->cookieFileName))
        {
            curl_setopt( $curl, CURLOPT_COOKIEJAR,  $this->cookieFileName );
            curl_setopt( $curl, CURLOPT_COOKIEFILE, $this->cookieFileName );
        }

        if ( $method == 'POST' )
        {
            curl_setopt( $curl, CURLOPT_POST, true);

            $postVars = self::normalizePostArray( $vars );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $postVars);
        }
        elseif (!empty($vars))
        {
            // append vars to url
            $query = http_build_query( $vars );

            if (!empty($query))
            {
                $queryChar = (strpos($url, '?') === false) ? '?' : '&';
                $url .= $queryChar . $query ;
            }
        }

        curl_setopt( $curl, CURLOPT_URL, $url);

        if (!empty($this->referer))
        {
            curl_setopt($curl, CURLOPT_REFERER, $this->referer);
        }

        $response = leafBotRequestResponse::getFromCurl( $curl );

        return $response;

    }

    public static function isCurrentUserAgent()
    {
        if (
            (
                (empty($_SERVER['HTTP_USER_AGENT']))
                ||
                (!preg_match(self::$userAgentPattern, $_SERVER['HTTP_USER_AGENT']))
            )
            &&
            (
                (empty($_GET['simulateLeafBot']))
            )
        )
        {
            return false;
        }

        return true;
    }

    public function setReferer( $referer )
    {
        $this->referer = $referer;

    }


    protected static function normalizePostArray( $vars )
    {
        if (!is_array($vars))
        {
            return $vars;
        }

        $returnArray = array();
        foreach ($vars as $key => $val)
        {
            if (is_array($val))
            {
                $val = self::normalizePostArray( $val );
                foreach ($val as $valKey => $val)
                {
                    $postKey   = $key . '[' . $valKey . ']';
                    $returnArray[$postKey] = $val;
                }
                unset($returnArray[$key]);
            }
            else
            {
                $returnArray[$key] = $val;
            }
        }

        return $returnArray;
    }
}


?>