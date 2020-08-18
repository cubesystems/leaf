<?
class fileDownload extends leafComponent{

	protected $headers = array(
		'swf' => 'application/x-shockwave-flash',
		'pdf' => 'application/pdf',
		'exe' => 'application/octet-stream',
		'zip' => 'application/zip',
		'doc' => 'application/msword',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',
        'jar' => 'application/java-archive',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'jpeg' => 'image/jpg',
		'jpg' => 'image/jpg',
		'mp3' => 'audio/mpeg',
		'wav' => 'audio/x-wav',
		'mpeg' => 'video/mpeg',
		'mpg' => 'video/mpeg',
		'mpe' => 'video/mpeg',
		'mov' => 'video/quicktime',
		'avi' => 'video/x-msvideo',
		'wmv' => 'video/x-ms-wmv',
		'html' => 'text/html',
		'txt' => 'text/plain',
		'*' => ' application/octet-stream',
	);
	protected $filePath;
	protected $downloadName;
	protected $extension;
	protected $allowedDownloadTypes = array('attachment', 'inline');
	protected $defaultDownloadType = 'attachment';

	function __construct($filePath = null, $downloadName = null)
	{
        if(!is_null($filePath))
        {
            $tmp = explode('/', $filePath);
            if(is_null($downloadName))
            {
                $downloadName = $tmp[sizeof($tmp) - 1];
            }
            $this->filePath = $filePath;
            $this->downloadName = $downloadName;
            $pathParts = pathinfo($downloadName);
            $this->extension = $pathParts['extension'];
            parent::__construct();
        }
	}
	
	public function download($type = NULL, $range = 0, $extraHeaderParams = array())
	{
		if(is_null($type) || !in_array($type, $this->allowedDownloadTypes))
		{
			$type = $this->defaultDownloadType;
		}
		if(@file_exists($this->filePath) === FALSE)
		{
			return;
		}
		session_write_close();
		dbClose();
		set_time_limit(0);
		$fp = fopen($this->filePath, "rb");
		$fsize = filesize($this->filePath);
		$ftime = date("D, d M Y H:i:s T", filemtime($this->filePath));
		if (isset($_SERVER['HTTP_RANGE']) && $_SERVER['HTTP_RANGE'])
		{
			list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			
			if ($size_unit == 'bytes')
			{
				//multiple ranges could be specified at the same time, but for simplicity only serve the first range
				//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
				list($requestedRange) = explode(',', $range_orig, 2);
				$requestedRangeTmp = explode('-', $requestedRange);
				$range = $requestedRangeTmp[0];
				if(isPositiveInt($requestedRangeTmp[1]))
				{
					$rangeEnd = $requestedRangeTmp[1];
				}
			}
		}
		
		if(!isset($rangeEnd))
		{
			$rangeEnd = ($fsize - 1);
		}
		
		if($range  > 0)
		{
			fseek($fp, $range);
			header("HTTP/1.1 206 Partial Content");
		}
		else
		{
			header("HTTP/1.1 200 OK");
        }

        $headers = array(
            'content-disposition'   => $type . '; filename="' . self::normalizeDownloadName($this->downloadName) . '"',
            'cache-control'         => 'private',
            'last-modified'         => $ftime,
            'accept-ranges'         => 'bytes',
            'content-range'         => 'bytes ' .$range. '-' .$rangeEnd. '/' .$fsize,
            'content-length'        => ($fsize - $range),
        );

        //Replace header params from $extraHeaderParams
        foreach( $extraHeaderParams as $key => $value )
        {
            $headers[strtolower($key)] = $value;
        }

        //Format header
        foreach ( $headers as $hKey => $hValue )
        {
            header($hKey. ': ' .$hValue);
        }

		if(!empty($this->headers[$this->extension]))
		{
			header('Content-type: ' . $this->headers[$this->extension]);
		}
		else
		{
			header('Content-type: ' . $this->headers['*']);
		}
		header("Connection: close");
		while(!feof($fp))
		{
			print(fread($fp, 4096));
			flush();
		}
		exit;
	}
	
	public static function normalizeDownloadName($string)
	{
		$processedString = str_replace(array('"', '/', '\\'), '', $string);
		return $processedString;
	}
}
?>
