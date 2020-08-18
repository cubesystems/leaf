<?

class banner
{
    var $classLocations;

    var $objectId;
    var $fileUrl;

    var $type;

    var $fileData;
    var $lastModified;

    var $banner;

    var $textMode = false;

    var $text;
    var $textClass;

    var $linkUrl;
    var $linkObject;

    var $url;

    protected $loadedFromObject = false;
    protected $loadedFromText   = false;
    protected $loadedFromUrl    = false;

    var $params = array(); // for type-specific custom stuff

    /*
    var $width;
    var $height;
    var $fileName;

    var $banner = null;


    */
    const cacheEnabled = true;

    protected static $bannerCache = array();

    function banner()
    {
        $this->classLocations = dirname(realpath(__FILE__)) . '/';
    }

    function loadFromObject($objectId)
    {
        $objectId = (int) $objectId;
        if (!$objectId)
        {
            return false;
        }
        $object = _core_load_object($objectId);

        if (!$object || $object->object_data['type'] != 21)
        {
            return false;
        }

        $this->objectId = $objectId;
        $this->fileUrl = orp($objectId);

        $this->fileData = $object->object_data['data'];
        $this->lastModified = $object->object_data['last_edit'];

        $bannerType = $this->getType();
        $this->loadBanner();
        $this->loadedFromObject = true;
        return true;
    }

    function loadFromText($text, $textClass, $pixelRatio = 1.0, $textStyle = null)
    {
        require_once SHARED_PATH . 'classes/image_text/_init.php';
        $imageText = singleton::get('image_text');
        if (!$imageText)
        {
            return false;
        }

        $this->setTextMode(true);
        $this->setText($text);
        $this->setTextClass($textClass);

        $fileName = $imageText->getImageFromText ($text, $textClass, $pixelRatio, true, $textStyle);

        if (!$fileName)
        {
            return false;
        }

        $fileDirUrl = $imageText->getImageCacheUrl();
        if (!$fileDirUrl)
        {
            return false;
        }

        $this->fileUrl = $fileDirUrl . $fileName;

        // load additional image info
        $imageInfo = $imageText->getImageInfo( $fileName );

        if (!$imageInfo)
        {
            return false; // could not load image info
        }
        // now $imageInfo must be converted to the format of files table
        // (assume that $imageInfo has all the necessary keys)

        $fileData = array(
            'extension' => $imageInfo['type'],
            'extra_info' => array(
                'original_width' => (int) $imageInfo['width'],
                'original_height' => (int) $imageInfo['height'],
                'image_width' => (int) floor( $imageInfo['width'] / $pixelRatio ),
                'image_height' => (int) floor( $imageInfo['height'] / $pixelRatio )
            ),
            'pixelRatio' => (double) $pixelRatio,
        );
        $this->fileData = $fileData;

        $bannerType = $this->getType();
        $this->loadBanner();

        $this->loadedFromText = true;
        return true;

    }

    function loadFromUrl( $url, $width, $height, $extension = null )
    {
        $this->fileUrl = $url;

        if ($extension === null)
        {
            // guess extension from url
            $url = parse_url($url);
            $pathParts = explode('/', $url['path']);
            $lastPart = array_pop($pathParts);
            if (!empty($lastPart))
            {
                $fileNameParts = explode('.', $lastPart);
                if (count($fileNameParts > 1))
                {
                    $extension = strtolower(array_pop($fileNameParts));
                }
            }
        }

        if (!$extension)
        {
            return false;
        }

        $fileData = array(
            'extension' => $extension,
            'extra_info' => array(
                'image_width' => $width,
                'image_height' => $height
            )
        );

        $this->fileData = $fileData;
        $bannerType = $this->getType();
        $this->loadBanner();

        $this->loadedFromUrl = true;
        return true;
    }


    function getType()
    {
        // if type already detected, return it
        if ($this->type)
        {
            return $this->type;
        }

        // else try to detect it from file data
        if (
            (!is_array($this->fileData))
            ||
            (!isset($this->fileData['extension']))
        )
        {
            return null;
        }

        $extension = $this->fileData['extension'];
        switch ($extension)
        {
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $type = 'image';
                break;
            case 'swf':
                $type = 'flash';
                break;
            default:
                $type = null;
        }

        $this->type = $type;
        return $this->type;
    }

    function loadBanner()
    {

        if (!$this->getType())
        {
            return false;
        }

        $type = strtolower($this->type);
        if (!preg_match('/^[a-z]+$/', $type)) // no funny stuff in type
        {
            return false;
        }

        require_once $this->classLocations . 'banner_type.class.php';

        $typeClass = strtolower(__CLASS__) . '_'.  $type;
        $classFile = $this->classLocations . $typeClass . '.class.php';
        if (!file_exists($classFile))
        {
            return false;
        }
        require_once $classFile;

        $banner = new $typeClass;
        $allOk = $banner->loadData( $this );
        if ($allOk)
        {
            $this->banner = $banner;
        }
        return $allOk;
    }

    function setLink($link = null) // accepts object id or url
    {
        if (!$link)
        {
            $this->linkUrl = null;
            $this->linkObject = null;
            return true;
        }


        if (isPositiveInt($link))
        {
            $this->linkObject = $link;
            if ($this->banner)
            {
                $this->banner->setAlt(object_name($link));
            }
        }
        elseif( $link instanceof leaf_object_module )
        {
            $this->linkObject = $link->object_data['id'];
            if ($this->banner)
            {
                $this->banner->setAlt($link->object_data['name']);
            }
        }
        else
        {
            $this->linkUrl = $link;
        }

        $url = $this->getUrl();

        $this->setUrl( $url );

        return true;
    }

    function setUrl( $url )
    {
        if ($this->banner)
        {
            $this->banner->setUrl( $url );
        }
        return;
    }

    function getUrl()
    {
        if ($this->linkUrl)
        {
            $url = $this->linkUrl;

            // look for '://' in url. add http:// to start if not found
            if (strpos($url,'://') === false)
            {
                $url = 'http://' . $url;
            }

        }
        elseif ($this->linkObject)
        {
            $url = orp($this->linkObject);
        }
        else
        {
            $url = null;
        }
        $this->url = $url;
        return $url;

    }

    function setTextMode($on = true)
    {
        $this->textMode = (bool) $on;
    }

    function setText($text)
    {
        $this->text = $text;
    }
    function setTextClass($class)
    {
        $this->textClass = $class;
    }

    function getHtml($params = array())
    {
        if (!$this->banner)
        {
            return null;
        }
        return $this->banner->getHtml( $params );
    }

    function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function getCachedHtml( $cacheKey )
    {
        if (!self::isCacheEnabled())
        {
            return null;
        }

        // look in memory cache first
        $memoryCacheHtml = $this->getHtmlFromMemoryCache( $cacheKey );
        if (!is_null($memoryCacheHtml))
        {
            return $memoryCacheHtml;
        }

        // not found in memory cache, look in db cache
        $dbCacheHtml = $this->getHtmlFromDbCache( $cacheKey );
        if (!is_null($dbCacheHtml))
        {
            // found in db cache, store in memory cache also
            $this->cacheHtmlInMemory($cacheKey, $dbCacheHtml);

            //stopwatch::mark('db cache USED for ' . $objectId);
            return $dbCacheHtml;
        }

        // not found in cache
        return null;
    }

    protected function getHtmlFromMemoryCache( $cacheKey )
    {
        $memoryCacheKey = $this->generateMemoryCacheKey( $cacheKey );
        if (isset(self::$bannerCache[$memoryCacheKey]))
        {
            return self::$bannerCache[$memoryCacheKey];
        }
        return null;
    }

    protected function getHtmlFromDbCache( $cacheKey )
    {
        if (empty($cacheKey))
        {
            return null;
        }

        $sql = '
            SELECT
                *
            FROM
                `bannerCache`
            WHERE
                cacheKey = "' . dbse($cacheKey) . '"
        ';

        $row = dbgetrow($sql);

        if (!is_array($row))
        {
            return null;
        }
        $html = $row['html'];
        return $html;
    }

    protected function generateCacheKey($params)
    {
		$params['host'] = WWW;
        return sha1(serialize($params));
    }

    protected function generateMemoryCacheKey( $cacheKey )
    {
        return $cacheKey; // same as db cache key
    }

    function cacheHtml($cacheKey, $html)
    {
        if (!self::isCacheEnabled())
        {
            return false;
        }

        $this->cacheHtmlInMemory($cacheKey, $html);
        $this->cacheHtmlInDb($cacheKey, $html);

        return true;
    }


    protected function cacheHtmlInMemory($cacheKey, $html)
    {
        $memoryCacheKey = $this->generateMemoryCacheKey( $cacheKey );
        self::$bannerCache[$memoryCacheKey] = $html;
        return true;
    }

    protected function cacheHtmlInDb($cacheKey, $html)
    {
        $sql = '
            REPLACE INTO `bannerCache`
            (cacheKey, cacheDate, html)
            VALUES
            ("' . dbse($cacheKey) . '", NOW(), "' . dbse($html) . '")
        ';
        dbquery($sql);
        return true;
    }

    public static function isCacheEnabled()
    {
        return (bool) self::cacheEnabled;
    }

    public function getCacheKey( $params )
    {
        unset($params['link']); // link may contain actual object
        if (empty($this->banner))
        {
            return null;
        }
        $params['url'] = $this->banner->url;


        if ($this->loadedFromObject)
        {
            if (
                (empty($this->objectId))
                ||
                (empty($this->lastModified))
            )
            {
                return null;
            }
            $params['objectId']          =  $this->objectId;
            $params['objectLastModified'] = $this->lastModified;
        }
        elseif ($this->loadedFromText)
        {
            $params['stylesheetLastModified'] = image_text::getStylesheetLastModTime();
        }
        elseif ($this->loadedFromUrl)
        {
            // nothing
        }
        else
        {
            return null;
        }

        $cacheKey = $this->generateCacheKey( $params );

        return $cacheKey;
    }
}

