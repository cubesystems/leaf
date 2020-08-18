<?
class imageBase extends leafComponent{
	protected $imageQuality = 80;
	protected $inputTypes = array('resource', 'file', 'string');
	protected $outputTypes = array('resource', 'file', 'display');

	protected $inputType = 'file';
	protected $outputType = 'file';
	protected $outputFilename = NULL;
	protected $source = NULL;
	protected $image = NULL;

	// error detection
	protected $ok = true;
	public 	  $error;

	// change file format
	protected $overrideFileFormat = NULL;

	public function initEnvironment(){
		parent::initEnvironment();

		// set custom jpg quality from value table or config
		// valua table setting overrides config setting
		if ($customQuality = getValue('components.image.quality'))
		{
			$this->setQuality($customQuality);
		}
		else
		{
		    $config = leaf_get('properties', 'imageBase');
		    if (
                (!empty($config))
                &&
                (!empty($config['quality']))
                &&
                (ispositiveint($config['quality']))
            )
            {
                $this->setQuality( $config['quality'] );
            }
		}
	}

	public function setQuality($quality){
		if(isPositiveInt($quality) && $quality > 0 && $quality < 101)
		{
			$this->imageQuality = $quality;
		}
	}

	public function setInputType( $type )
	{
	    if (in_array($type, $this->inputTypes))
	    {
	        $this->inputType = $type;
	        return true;
	    }
	    die ('imageBase: unsupported input type: ' . $type);
	}

	public function setOutputType( $type )
	{
	    if (in_array($type, $this->outputTypes))
	    {
	        $this->outputType = $type;
	        return true;
	    }
	    die ('imageBase: unsupported output type: ' . $type);
	}

	protected function processInput($sourceResource, $params = array()){
		// detect input type
		if (is_resource($sourceResource))
		{
			$this->inputType = 'resource';
		}
		elseif (!empty($params['inputType']) && in_array($params['inputType'], $this->inputTypes))
		{
			$this->inputType = $params['inputType'];
		}
		// for compatibility
		if(!empty($params['targetType']))
		{
			$params['outputType'] = $params['targetType'];
		}
		// auto set output type
		if(!empty($params['targetFile']) && empty($params['outputType']))
		{
			$params['outputType'] = 'file';
		}
		// detect target type
		if(!empty($params['outputType']) && in_array($params['outputType'], $this->outputTypes))
		{
			$this->outputType = $params['outputType'];
		}
		// set custom jpg quality
        if ( (!empty($params['quality'])) && (ispositiveint($params['quality'])) )
        {
            $this->setQuality( $params['quality'] );
        }

		// set target file
		if($this->outputType == 'file')
		{
			if(!empty($params['targetFile']))
			{
				$this->outputFilename = $params['targetFile'];
			}
			elseif($this->inputType == 'file')
			{
				$this->outputFilename = $sourceResource;
			}
			else
			{
				$this->outputType = 'display';
			}
		}

		// set source
		if($this->inputType == 'string')
		{
			$this->source = imagecreatefromstring($sourceResource);
		}
		elseif($this->inputType == 'file')
		{
			$info = getimagesize($sourceResource);
			switch ($info[2])
			{
				case IMAGETYPE_JPEG:
				case IMAGETYPE_JPEG2000:
					$this->source = imagecreatefromjpeg($sourceResource);
					break;
				case IMAGETYPE_GIF:
					$this->source = imagecreatefromgif($sourceResource);
					break;
				case IMAGETYPE_PNG:
					$this->source = imagecreatefrompng($sourceResource);
					imagealphablending( $this->source, false ); // otherwise imagesetpixel breaks alpha channel
					imagesavealpha( $this->source, true );      // required to preserve png's alpha channel
					break;
				default:
					$this->source = false;
					break;
			}
			if($this->source === false)
			{
				$this->ok = false;
				$this->error = 'imageBase error : image creation from file failed';
			}
		}
		else
		{
			$this->source = $sourceResource;
		}
	}

	protected function returnResource(){
		if($this->outputType == 'file')
		{
			$parts = explode( '.', $this->outputFilename );
			if( $parts[  count($parts) - 1 ] == 'png' )
			{
				imagepng($this->image, $this->outputFilename);
			}
			elseif( $parts[  count($parts) - 1 ] == 'gif' )
			{
				imagegif($this->image, $this->outputFilename);
			}
			else
			{
				imagejpeg($this->image, $this->outputFilename, $this->imageQuality);
			}
			imagedestroy($this->image);
		}
		elseif($this->outputType == 'resource')
		{
			return $this->image;
		}
		elseif($this->outputType == 'display')
		{
			header('Content-type: image/jpeg');
			imagejpeg($this->image, false, $this->imageQuality);
			imagedestroy($this->image);
			exit;
		}
	}
}
?>