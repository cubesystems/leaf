<?

class pngEdit extends leafComponent
{
    // http://www.w3.org/TR/2003/REC-PNG-20031110/

    const pngHeaderAscii = '137 80 78 71 13 10 26 10';
    protected $pngHeader = null;

    protected $headerLength = null;

    protected $fileName = null;
    protected $file = null;
    protected $fileSize = null;

    protected $fileHeader = null;
    protected $fileChunks = array();

    public function __construct()
    {
        $headerCodes = explode(' ', self::pngHeaderAscii );
        $headerStr = '';
        foreach ($headerCodes as $code)
        {
            $headerStr .= chr($code);
        }
        $this->pngHeader = $headerStr;
        $this->headerLength = strlen($this->pngHeader);
    }

    public function loadFile( $fileName )
    {
        // open file for reading
        $this->file = fopen($fileName, 'rb');
        if ($this->file === false)
        {
            return false;
        }
        $this->fileSize = filesize( $fileName );

        // validate png header
        $fileHeader = fread( $this->file, $this->headerLength);
        if ($fileHeader != $this->pngHeader)
        {
            fclose( $this->file );
            return false;
        }

        $this->fileName   = $fileName;
        $this->fileHeader = $fileHeader;

        $parseOk = $this->parseFile();
        fclose( $this->file );
        return $parseOk;
    }

    protected function parseFile()
    {
        $seekOk = fseek ($this->file, $this->headerLength);
        if ($seekOk !== 0)
        {
            return false;
        }

        $totalChunksLength = 0;
        $chunks = array();
        while (!feof($this->file))
        {
            $chunkLengthBin = fread($this->file, 4);
            if (strlen($chunkLengthBin) != 4)
            {
                continue;
            }

            $chunkLength = self::get32bitIntFromBinary( $chunkLengthBin );
            $chunkType = fread( $this->file, 4);

            $chunkData = '';
            if ($chunkLength > 0)
            {
                $chunkData = fread ( $this->file, $chunkLength );
            }

            $chunkCrc = fread ( $this->file, 4);

            $chunkByteLength =
                  strlen( $chunkLengthBin )
                + strlen( $chunkType )
                + strlen( $chunkData )
                + strlen( $chunkCrc )
            ;

            $totalChunksLength += $chunkByteLength;
            $chunks[] = array (
                'length'        => $chunkLength,
                'lengthBin'     => $chunkLengthBin,
                'type'          => $chunkType,
                'data'          => $chunkData,
                'crc'           => $chunkCrc,
                'byteLength'    => $chunkByteLength
            );
        }

        // verify byte length
        $parsedLength = $totalChunksLength + $this->headerLength;
        if ($parsedLength != $this->fileSize)
        {
            return false;
        }

        $this->fileChunks = $chunks;

        return true;
    }

    protected function removeChunk( $type )
    {
        $removeKeys = array();

        foreach ($this->fileChunks as $key => $chunk)
        {
            $chunkType = $chunk['type'];
            if ($chunkType == $type)
            {
                $removeKeys[] = $key;
            }
        }

        foreach ($removeKeys as $key)
        {
            unset ($this->fileChunks[$key]);
        }
        return null;
    }

    protected function writeToFile()
    {
        $fileStr = $this->getFileString();

        $this->file = fopen( $this->fileName, 'wb');
        $writtenBytes = fwrite( $this->file, $fileStr);
        fclose( $this->file );
        clearstatcache();

        if ($writtenBytes === false)
        {
            return false;
        }
        return true;
    }

    protected function getFileString()
    {
        $chunkStrings = array();
        foreach ($this->fileChunks as $chunk)
        {
            $chunkStrings[] = $chunk['lengthBin'] . $chunk['type'] . $chunk['data'] . $chunk['crc'];
        }
        $chunksAsString = implode('', $chunkStrings);

        $fileString = $this->fileHeader . $chunksAsString;
        return $fileString;
    }

    protected static function get32bitIntFromBinary($binaryStr)
    {
        $length = 4;
        if (strlen($binaryStr) != $length)
        {
            return null;
        }

        $valueInt = 0;

        for ($i = 0; $i < $length; $i++)
        {
            $byteStr = substr($binaryStr, $i, 1);
            $byteInt = ord($byteStr);
            $valueInt = ($valueInt << 8) | $byteInt;
        }

        return $valueInt;
    }




    public static function removeGammaFromFile( $fileName )
    {
        return self::removeChunkFromFile( $fileName, 'gAMA');
    }

    public static function removeChunkFromFile( $fileName, $chunkType)
    {
        if (!self::isValidChunkTypeName($chunkType))
        {
            return false;
        }

        $png = new pngEdit;
        if (!$png->loadFile($fileName))
        {
            return false;
        }

        $png->removeChunk( $chunkType );
        return $png->writeToFile();

    }

    public static function isValidChunkTypeName($chunkType)
    {
        return (bool) preg_match('/^[a-zA-Z]{4}$/', $chunkType);
    }

}

?>