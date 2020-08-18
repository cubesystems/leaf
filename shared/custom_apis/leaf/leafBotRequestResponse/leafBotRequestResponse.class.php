<?


class leafBotRequestResponse extends leafBaseObject
{

    const httpHeaderNameValueSeparator  = ':';
    const httpHeaderEndSequence = "\r\n";
    const httpHeadersEndSequence = "\r\n\r\n";

    const leafBotHeader = 'leafbot-response-type';
    const leafBotHeaderValueJson = 'json';

    protected $statusLine = null;
    protected $contentType = null;

    protected $leafBotResponseType = null;
    protected $customErrors = array();

    protected $headers = null;
    protected $info = null;
    protected $body = null;

    protected $rawHeaders;
    protected $rawBody;
    protected $rawResponse;

    protected $headersEnd;

    function __construct( )
    {

    }

    public static function getFromCurl( $curlInstance )
    {
        $instance = new leafBotRequestResponse;
        $instance->loadFromCurlInstance( $curlInstance );
        return $instance;
    }

    public function loadFromCurlInstance( $curl )
    {
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt( $curl, CURLOPT_HEADER, true);
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

        $this->rawResponse = curl_exec( $curl );
        $this->info = curl_getinfo( $curl );


        if ($this->rawResponse === false)
        {
            return false;
        }

        // response received, parse it

        // separate headers from body
        $headersEndPos = strpos( $this->rawResponse, self::httpHeadersEndSequence );
        if ($headersEndPos !== false)
        {
            $this->rawHeaders = substr($this->rawResponse, 0, $headersEndPos);
            $this->rawBody    = substr($this->rawResponse, $headersEndPos + strlen(self::httpHeadersEndSequence) );
        }
        $this->parseRawHeaders();

        if ($this->isLeafBotResponse())
        {
            $this->parseLeafBotResponse();
        }
        else
        {
            $this->body = $this->rawBody;
        }

        return true;
    }

    public function isOk()
    {
        if (
            ($this->rawResponse === false)
            ||
            (empty($this->info))
            ||
            (empty($this->info['http_code']))
            ||
            ($this->info['http_code'] != '200')
        )
        {
            return false;
        }

        return true;
    }

    public function isJsonOk()
    {
        if (
            (!$this->isOk()) // response fail
            ||
            (!$this->isLeafBotResponse())
            ||
            ($this->leafBotResponseType != self::leafBotHeaderValueJson)  // not a json response
            ||
            (!empty($this->customErrors)) // error decoding json
        )
        {
            return false;
        }
        return true;
    }

    public function isLeafBotResponse()
    {
        return (!is_null($this->leafBotResponseType));
    }

    protected function parseRawHeaders()
    {
        if (is_null($this->rawHeaders))
        {
            return false;
        }

        $lines = explode(self::httpHeaderEndSequence, $this->rawHeaders);
        if (empty($lines))
        {
            return false;
        }

        // first line is status line
        $this->statusLine = $lines[0];
        unset($lines[0]);

        // the rest are headers
        $contentType = $leafBotResponseType = null;

        $headers = array();
        foreach ($lines as $line)
        {
            $separatorPos = strpos($line, self::httpHeaderNameValueSeparator );
            if ($separatorPos === false)
            {
                continue;
            }

            $headerName  = strtolower(trim(substr($line, 0, $separatorPos)));
            $headerValue = trim(substr($line, $separatorPos + 1));

            $headers[] = array(
                'name' => $headerName,
                'value' => $headerValue
            );

            if ($headerName == 'content-type')
            {
                $contentType = $headerValue;
            }
            elseif ($headerName == self::leafBotHeader)
            {
                $leafBotResponseType = $headerValue;
            }

        }

        $this->headers             = $headers;
        $this->contentType         = $contentType;
        $this->leafBotResponseType = $leafBotResponseType;

    }

    protected function parseLeafBotResponse()
    {
        $type = $this->leafBotResponseType;
        switch ($type)
        {
            case self::leafBotHeaderValueJson:
                $body = json_decode( trim($this->rawBody), true );
                if (!is_array($body))
                {
                    $this->customErrors[] = 'json_decode() on body failed.';
                    return false;
                }
                $this->body = $body;
                break;

            default:
                return false;
        }
        return true;
    }




    // for sending data
    public static function sendJson( $data )
    {
        $data = json_encode( $data );
        leafHttp::header(self::leafBotHeader, self::leafBotHeaderValueJson);
        die( $data );
    }


}

?>