<?

class leafHttp
{


    public static function redirect( $url, $permanentlyMoved = false )
    {
        if ($permanentlyMoved)
        {
            header('HTTP/1.1 301 Moved Permanently');
        }

        self::header('Location', $url);
        die();
    }
    
    public static function send403()
    {
        header('HTTP/1.0 403 Forbidden');
        
        require_once(SHARED_PATH . 'classes/leaf_error/leaf_error.class.php');
        $leaf_error = new leaf_error;

        $message = array
        (
            'header' => '403 Forbidden',
            'html' => '<p>You don\'t have permission to access this resource on this server.</p>'
        );

        $leaf_error->addHeader(
        '
            <meta http-equiv="imagetoolbar" content="no" />
            <meta http-equiv="cache-control" content="no-cache" />
            <meta http-equiv="pragmas" content="no-cache" />
            <meta name="robots" content="noindex,nofollow" />
            <meta name="googlebot" content="noindex,nofollow" />
            <meta name="googlebot" content="noarchive" />
            <meta name="robots" content="noimageindex" />
            <meta name="robots" content="nocache,noarchive" />
        ');
        
        $leaf_error->addMessage($message);
        $leaf_error->display(); // dies here
        
    }

    public static function header($name, $value)
    {
        $value = preg_replace( '/(\r|\n|\x00)/', ' ', trim($value) );
        header($name . ': ' . $value);
    }

    public static function isContentModified( $httpLastModified, $eTag )
    {
        // accepts lastmodified and etag headers of the content to be sent

        // checks if-modified-since and if-none-match headers in request
        // if at least one of them is set
        // and the set ones match the current values of $lastModified / $eTag,
        // the content is not modified

        $ifModifiedSince = (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : null;
        $ifNoneMatch     = (!empty($_SERVER['HTTP_IF_NONE_MATCH']))     ? $_SERVER['HTTP_IF_NONE_MATCH']     : null;

        $isModified = true;
        if (
            (
                // at least one of both headers is set
                (!empty($ifModifiedSince)) || (!empty($ifNoneMatch))
            )
            &&
            (
                // if-modified-since is either empty or matches the current
                ((empty($ifModifiedSince)) || ($ifModifiedSince == $httpLastModified))
                &&
                // if-none-match is either empty or matches the current
                ((empty($ifNoneMatch)) || ($ifNoneMatch == $eTag))
            )
        )
        {
            $isModified = false;
        }
        return $isModified;

    }

    public static function hanldeConditionalGet( $lastModifiedStr )
    {
        if (!$lastModifiedStr)
        {
            return false;
        }

        $httpLastModified = self::getHttpDate( $lastModifiedStr );
        if (!$httpLastModified)
        {
            return false;
        }

        $eTag = self::getEtag( $httpLastModified );

        if (!self::isContentModified($httpLastModified, $eTag))
        {
            header('HTTP/1.0 304 Not Modified');
            die();
        }

        self::header('Last-Modified', $httpLastModified);
        self::header('ETag', $eTag);
        return true;
    }

    public static function getEtag( $str )
    {
        return '"' . md5($str) . '"';
    }

	public static function getHttpDate( $dateStr )
	{
	    // returns rfc1123-date according to rfc2616 section 3.3.1
	    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1

	    $timestamp = strtotime($dateStr);

	    // rfc1123-date	= wkday "," SP date1 SP time SP "GMT"
	    $format = 'D, d M Y H:i:s \G\M\T';

	    $gmdate = gmdate($format, $timestamp);
	    return $gmdate;
	}


}


?>
