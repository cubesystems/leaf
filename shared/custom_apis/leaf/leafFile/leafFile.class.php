<?

class leafFile extends leafBaseObject
{
	const tableName = 'leafFiles';

	const defaultFolderMode = 0775; // rwx rwx r-x
	const defaultFileMode   = 0666; // rw- rw- rw-
    const inputFieldSuffix  = '_file';

	protected
	   $ownerClass,
	   $ownerObjectId,
	   $path,
	   $originalName,
	   $type,

	   $add_date,
	   $author_ip
    ;

    protected $fileName;

	protected $fieldsDefinition = array
	(
		'ownerClass' => array
		(
			'not_empty' => true
		),
		'ownerObjectId' => array
		(
		    'type' => 'int',
		    'not_empty' => true,
		),
		'path' => array
		(

		),
		'type' => array
		(
		),
		'originalName' => array
		(

		)
	);

    protected static $_tableDefsStr = array
    (
        self::tableName => array (
            'fields' =>
            '
                id                  int auto_increment
                ownerClass      	varchar(255)
                ownerObjectId       int
                path                text
                originalName        text
                type                varchar(255)
                add_date			datetime
                author_ip           varchar(255)
            '
            ,
            'indexes' => '
                primary id
                index owner ownerClass 64, ownerObjectId
                index type
            ',
            'engine' => 'innodb'
        )
    );



	public static function _autoload( $className )
    {
        parent::_autoload( $className );
        dbRegisterRawTableDefs( self::$_tableDefsStr );
    }

    public static function create( $uploadData, $owner, $ownerObjectField = null)
    {
        if (!is_array($uploadData))
        {
            return null;
        }
        unset( $uploadData['localFile'] );
        return self::createInternal( $uploadData, $owner, $ownerObjectField);
    }

    public static function createFromLocalFile( $fileData, $owner, $ownerObjectField = null )
    {
        if (
            (!is_array($fileData))
            ||
            (empty($fileData['name']))
            ||
            (empty($fileData['tmp_name']))
            )
        {
            return null;
        }

        $actualFileData = array
        (
            'name'     => $fileData['name'],
            'tmp_name' => $fileData['tmp_name'],
            'localFile' => true
        );
        return self::createInternal( $actualFileData, $owner, $ownerObjectField);
    }

    protected static function createInternal( $inputFileData, $owner, $ownerObjectField = null)
    {
        // returns object
        // accepts $owner as either an instance of leafbaseobject
        // or  array('class' => 'someClass', 'id' => 123) (id is optional)

        if (!is_array($inputFileData))
        {
            return null;
        }

        $localFileMode = (!empty($inputFileData['localFile']));
        $uploadMode = !$localFileMode;


        if (is_object($owner))
        {
            $ownerClass    = get_class( $owner );
            $ownerObjectId = $owner->id;
            $ownerObject   = $owner;
        }
        elseif (
            (is_array($owner))
            &&
            (!empty($owner['class']))
            &&
            (is_string($owner['class']))
        )
        {
            $ownerObject  = null;
            $ownerClass   = $owner['class'];

            if (
            (isset($owner['id']))
            &&
            (isPositiveInt($owner['id']))
            )
            {
                $ownerObjectId = $owner['id'];
            }
            else
            {
                $ownerObjectId = null;
            }
        }
        else
        {
            // FAIL
            return null;
        }



        $fileData = array
        (
            'ownerClass'       => $ownerClass,
            'ownerObjectId'    => $ownerObjectId,
            'ownerObjectField' => $ownerObjectField,
            'originalName'     => $inputFileData['name'],
            'ownerObject'      => $ownerObject
        );

        if ($uploadMode)
        {
            $fileData['type'] = $inputFileData['type'];
        }
        elseif ($localFileMode)
        {
            $fileData['type'] = self::getFileType( $inputFileData['tmp_name'] );
        }
        else
        {
            return null;
        }


        $fileData['path'] = self::getNewFilePath( $fileData );
        
        if (empty($fileData['path']))
        {
            return null;
        }

        if (
            ($uploadMode)
            &&
            (!is_uploaded_file($inputFileData['tmp_name']))
        )
        {
            return null;
        }
        elseif (
            ($localFileMode)
            &&
            (!is_writable($inputFileData['tmp_name']))
        )
        {
            return null;
        }

      
        $pathParts = pathinfo($fileData['path']);
 
        $dirName   = $pathParts['dirname'];
        $fileName  = $pathParts['filename'] . ((!empty($pathParts['extension'])) ? '.' . $pathParts['extension'] : '');

        $root      = self::getRoot( $fileData );
        
        $fullDirName = $root . $dirName;

        if (!file_exists($fullDirName))
        {
            $newDirPermissions = self::getNewDirMode( $fileData );
            $makeOk = @mkdir($fullDirName, $newDirPermissions, true);
            if (!$makeOk)
            {
                return null;
            }
            @chmod($fullDirName, $newDirPermissions); // repeat chmod, mkdir permissions sometimes do not work

        }

        $fullDirName = realpath( $fullDirName );

        if ((!$fullDirName) || (!is_dir($fullDirName)) || (!is_writable($fullDirName)))
        {
            return null;
        }

        $targetFile = $fullDirName . '/' . $fileName;

        if ($uploadMode)
        {
            $movedOk = @move_uploaded_file( $inputFileData['tmp_name'], $targetFile);
            if (!$movedOk)
            {
                return null;
            }
        }
        elseif ($localFileMode)
        {
            $renameOk = @rename( $inputFileData['tmp_name'], $targetFile);
        }
        else
        {
            return null;
        }


        @chmod($targetFile, self::getNewFileMode( $fileData ));

        unset( $fileData['ownerObjectField'], $fileData['ownerObject']  );
        $file = getObject(__CLASS__, 0);
        $file->assignArray($fileData);
        $file->save();
        return $file;
    }

    
    protected static function getRoot( $fileDataOrLeafFile )
    {
        $root = null;

        $ownerClass = null;
        
        if ( is_object($fileDataOrLeafFile) && $fileDataOrLeafFile instanceof leafFile)
        {
            $ownerClass = $fileDataOrLeafFile->ownerClass;
        }        
        elseif
        (
            (is_array($fileDataOrLeafFile))
            &&
            (!empty($fileDataOrLeafFile['ownerClass']))
        )
        {
            $ownerClass = $fileDataOrLeafFile['ownerClass'];
        }
        
        
        if ($ownerClass && method_exists($ownerClass, 'getFileRoot'))
        {
            $root = call_user_func( array($ownerClass, 'getFileRoot') );
        }

        if (!$root)
        {
            $root = LEAF_FILE_ROOT;
        }
        
        return $root;
    }

	protected static function getNewDirMode( $fileData )
	{
	    // custom permission settings depending on $fileData may be implemented if needed
        return self::defaultFolderMode;
	}

	protected static function getNewFileMode( $fileData )
	{
	    // custom permission settings depending on $fileData may be implemented if needed
        return self::defaultFileMode;
	}


	public static function get($ownerClass, $ownerObjecId, $fileId)
	{
        if (
            (empty($ownerClass))
            ||
            (!is_string($ownerClass))
            ||
            (empty($ownerObjecId))
            ||
            (!ispositiveint($ownerObjecId))
            ||
            (empty($fileId))
            ||
            (!ispositiveint($fileId))
        )
        {
            return null;
        }

        $isOk = self::existsAndBelongsTo( $fileId, $ownerClass, $ownerObjecId );
        if (!$isOk)
        {
            return null;
        }

        $file = getObject(__CLASS__, $fileId);
        if (!$file)
        {
            return null;
        }
        return $file;
	}


	public static function send( $ownerClass, $ownerId, $id, $downloadType = null)
	{
	    $file = self::get( $ownerClass, $ownerId, $id);
        if (
            (!$file)
            ||
            (empty($file->path))
        )
        {
            die ('file not found');
        }
        return $file->download($downloadType);
	}


    
	protected static function getNewFilePath( $fileData )
    {
        $dir = null;
	    if (
            (is_object($fileData['ownerObject']))
            &&
            (method_exists($fileData['ownerObject'], 'getRelativeFilePath'))
        )
        {
            $dir = $fileData['ownerObject']->getRelativeFilePath( $fileData );
        }

        // use default dir, if no custom folder is set
        if(is_null($dir)) // store in class folder by default
        {
            $dir = $fileData['ownerClass'] . '/';
        }


	    $fileName = $fileData['originalName'];


	    $fileNameParts = pathinfo($fileName);


	    $namePart = (empty($fileNameParts['filename']))  ? '' : $fileNameParts['filename'];
	    $extPart  = (empty($fileNameParts['extension'])) ? '' : $fileNameParts['extension'];

	    $namePart = stringtolatin($namePart, true);
        if (strlen($extPart) > 0)
        {
            $extPart = '.' . stringtolatin($extPart, true);
        }

        $fileName = $namePart . $extPart;
        $limit = 1000;
        $i = 0;
        
        $root = self::getRoot( $fileData );
        
        while (file_exists($root . $dir . $fileName) && $i < $limit)
        {
            $i++;
            $fileName = self::getRandomFileName($namePart, $extPart);
        }
        if ($i == $limit)
        {
            // exceeded safety limit
            return null;
        }

        return $dir . $fileName;
	}


	protected static function getRandomFileName($namePart, $extPart)
	{
	    $suffix = '_' . substr(md5(rand()), 0, 8);

	    return $namePart . $suffix . $extPart;
	}

	public function delete( $deleteFolderIfLastFile = false )
	{
        // delete child objects
        if (!empty($this->id))
        {
            $children = self::getAllByOwner( __CLASS__, $this->id );
            foreach ($children as $child)
            {
                $child->delete( true );
            }
        }

	    $fullPath = $this->getFullPath();

	    if (
	       (!empty($fullPath))
	       &&
	       (file_exists($fullPath))
	       &&
	       (is_file($fullPath))
        )
        {

            @unlink( $fullPath );
            if ($deleteFolderIfLastFile)
            {
                $dir = dirname( $fullPath );
                if ( self::isDirEmpty( $dir ) )
                {
                    @rmdir ( $dir );
                }
            }
        }

        return parent::delete();
	}

	public static function isDirEmpty( $dir )
	{

        if (
            (!is_dir($dir))
            ||
            (!is_readable($dir))
        )
        {
            return false; // not dir or cant tell if empty
        }

        $files = scandir( $dir );

        if (!is_array($files))
        {
            return false; // file listing failed
        }

        foreach ($files as $file)
        {
            if (
                ($file == '.') || ($file == '..')
            )
            {
                continue; // skip these
            }

            return false; // file found, dir not empty
        }

        return true;
	}

    public function hasStoredFile()
    {
        $fullPath = $this->getFullPath();

        return (
            file_exists($fullPath)
            &&
            is_file($fullPath)
            &&
            is_readable($fullPath)
        );
    }

	public function download($downloadType = null)
	{
        if ( ! $this->hasStoredFile() )
        {
            die ('file not found');
        }

        $fullPath = $this->getFullPath();
        $download = new fileDownload($fullPath, $this->originalName);
        if(!is_null($downloadType))
        {
            $type = $downloadType;
        }
        else
        {
            $type = (empty($this->type)) ? null : $this->type;
        }

        return $download->download($type);
	}

	public function getFullPath()
	{
        $root = self::getRoot( $this );
        
	    $fullPath = realpath( $root . $this->path );

	    return $fullPath;
	}

	public function getFullUrl()
	{
	    $url = LEAF_FILE_ROOT_WWW . $this->path;
	    return $url;
	}

	public function getFileName()
	{
	    if (empty($this->fileName))
	    {
	        $this->fileName = basename( $this->path );
	    }
	    return $this->fileName;
	}



	protected static function existsAndBelongsTo( $fileId, $ownerClass, $ownerObjecId )
	{
	    if (
	       (!isPositiveInt($fileId))
	       ||
	       (!isPositiveInt($ownerObjecId))
	       ||
	       (!is_string($ownerClass))
	       ||
	       (empty($ownerClass))
        )
        {
            return false;
        }

        $queryParts = array();
		$queryParts['select'] = 'id';
		$queryParts['from'] =  '`' . leafBaseObject::getClassTable(__CLASS__) . '` `c`';
		$queryParts['where'] = array
		(
            'id = ' . $fileId,
            'ownerClass = "' . dbse($ownerClass) . '"',
            'ownerObjectId = ' . $ownerObjecId
		);

		$id = dbgetone( $queryParts );
		if ($fileId != $id)
		{
		    return false;
		}
		return true;
	}



	public static function getCollection(  $params = array (), $itemsPerPage = null, $page = null )
	{
		$queryParts = array();
		$queryParts['select'][] = 'c.*';
		$queryParts['from'][] =  '`' . leafBaseObject::getClassTable(__CLASS__) . '` `c`';
		$queryParts['groupBy'][] = 'c.id';

		if (!empty($params['ownerClass']))
		{
		    $queryParts['where'][] = 'c.ownerClass = "' . dbse($params['ownerClass']) . '"';
		}

		if (
            (!empty($params['ownerObjecId']))
            &&
            (isPositiveInt($params['ownerObjecId']))
        )
		{
		    $queryParts['where'][] = 'c.ownerObjectId = ' . $params['ownerObjecId'];
		}


        $collection = new pagedObjectCollection( __CLASS__, $queryParts );

		return $collection;
	}


	public static function getAllByOwner( $ownerClass, $ownerObjecId )
	{
	    $params = array(
            'ownerClass'   => $ownerClass,
            'ownerObjecId' => $ownerObjecId
	    );
        return self::getCollection( $params );
	}


    public static function getFileType( $fileName )
    {
        $contentType = null;

        if (!file_exists($fileName))
        {
            return null;
        }

        if (function_exists('finfo_open'))
        {
            $finfo       = finfo_open(FILEINFO_MIME);
            $contentType = finfo_file( $finfo, $fileName );
        }
        elseif (function_exists('mime_content_type'))
        {
            $contentType = mime_content_type( $fileName );
        }

        if (empty($contentType))
        {
            return null;
        }

        $contentType = explode(';', $contentType);
        $contentType = trim($contentType[0]);

        return $contentType;
    }

	public function getUrl()
	{
	    // returns default url to file
	    return LEAF_FILE_ROOT_WWW . $this->path;
	}


    public static function getFreeFileName( $fullDirPath, $originalFileName = null, $nameSuffix = null )
    {
        if (
            (file_exists($fullDirPath))
            &&
            (!is_dir($fullDirPath))
        )
        {
            return null;
        }

        if (empty($originalFileName))
        {
            $namePart = sha1(str_repeat(uniqid(), 100));
            $extPart  = '';
        }
        else
        {
            $fileNameParts = pathinfo($originalFileName);

            $namePart = (empty($fileNameParts['filename']))  ? '' : $fileNameParts['filename'];
            $extPart  = (empty($fileNameParts['extension'])) ? '' : $fileNameParts['extension'];

            $namePart = stringtolatin($namePart, true);
            if (strlen($extPart) > 0)
            {
                $extPart = '.' . stringtolatin($extPart, true);
            }
        }

        if (!is_null($nameSuffix))
        {
            $namePart .= $nameSuffix;
        }


        $fileName = $namePart . $extPart;
        $limit = 1000;
        $i = 0;
        while (file_exists($fullDirPath . $fileName) && $i < $limit)
        {
            $i++;
            $fileName = self::getRandomFileName($namePart, $extPart);
        }
        if ($i == $limit)
        {
            // exceeded safety limit
            return null;
        }
        return $fileName;
    }

}

