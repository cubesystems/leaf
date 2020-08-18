<?

class leafMimeType
{
    
    public static function getFromFilename( $fileName )
    {
        $pathParts = pathinfo( $fileName );
        $extension = get( $pathParts, 'extension' );
        
        return self::getFromExtension( $extension );
    }
    
    
    public static function getFromExtension( $extension )
    {
        if( !$extension )
        {
            return null;
        }
        
        $mimeTypes = self::getMimeTypes();
        
        if( array_key_exists( $extension, $mimeTypes ) )
        {
            return $mimeTypes[ $extension ];
        }
        
        return null;
    }
    
    
    public static function getMimeTypes()
    {
        $mimeTypes          = array();
        $mimeTypesFile      = __DIR__ . '/mime.types';
        $cachedMimeTypes    = CACHE_PATH . 'mime.types.cached';
        
        if( file_exists( $cachedMimeTypes ) )
        {
            $content = file_get_contents( $cachedMimeTypes );
            $mimeTypes = unserialize( $content );
            
            if( $mimeTypes )
            {
                return $mimeTypes;
            }
        }
        
        if( !file_exists( $mimeTypesFile  ) )
        {
            return null;
        }
        
        $lines = file( $mimeTypesFile, FILE_IGNORE_NEW_LINES );
        
        if( sizeof( $lines ) > 0 )
        {
            foreach( $lines as $line )
            {
                if ( substr($line, 0, 1) == '#' || !preg_match( "/([\w\+\-\.\/]+)\t+([\w\s]+)/i", $line, $matches ) )
                {
                    continue;
                }
                
                $extensions = explode(" ", $matches[2]);
                $mime = $matches[1];
                
                foreach( $extensions as $ext )
                {
                    $mimeTypes[ trim( $ext ) ] = $mime;
                }
            }
            
            file_put_contents( $cachedMimeTypes, serialize( $mimeTypes ) );
        }
        
        return $mimeTypes;
    }
    
    
    
}