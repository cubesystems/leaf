<?
class imageCropCoords extends imageBase
{

	public function getCropCoords($params)
	{
		// if only width or only height given, crop square
		if (!empty($params['width']) && empty($params['height']))
		{
			$params['height'] = $params['width'];
		}
		elseif(!empty($params['height']) && empty($params['width']))
		{
			$params['width'] = $params['height'];
		}

		// dimensions of new image
		$params['targetImageWidth']  = $params['width'];
		$params['targetImageHeight'] = $params['height'];


        // measure current dimensions of source image
		$sourceWidth  = imagesx($this->source);
		$sourceHeight = imagesy($this->source);


		// if source image is smaller than requested crop area,
		// reduce crop area
		if ($sourceWidth < $params['targetImageWidth'])
		{
		    $params['cropWidth'] = $sourceWidth;
		}
		else
		{
		    $params['cropWidth'] = $params['targetImageWidth'];
		}
		if ($sourceHeight < $params['targetImageHeight'])
		{
		    $params['cropHeight'] = $sourceHeight;
		}
		else
		{
		    $params['cropHeight'] = $params['targetImageHeight'];
		}
		unset ($params['width'], $params['height']);



		// if left or top positions not given or invalid, set to 0
		if (
            (empty($params['x']))
            ||
            (!ispositiveint($params['x']))
        )
		{
		    $params['x'] = 0;
		}

		if (
            (empty($params['y']))
            ||
            (!ispositiveint($params['y']))
        )
		{
		    $params['y'] = 0;
		}


		$params['cropLeft'] = $params['x'];
		$params['cropTop']  = $params['y'];


		if ($params['cropWidth'] != $params['targetImageWidth'])
		{
		    $params['targetLeft'] = floor( ($params['targetImageWidth'] - $params['cropWidth']) / 2 );
		}
		else
		{
		    $params['targetLeft'] = 0;
		}

		if ($params['cropHeight'] != $params['targetImageHeight'])
		{
		    $params['targetTop'] = floor( ($params['targetImageHeight'] - $params['cropHeight']) / 2 );
		}
		else
		{
		    $params['targetTop'] = 0;
		}
		unset ($params['x'], $params['y']);

		return $params;
	}

	public function processInput($sourceResource, $params = array())
	{
		parent::processInput($sourceResource, $params);

		$cropCoords = $this->getCropCoords($params);

		$this->image = imagecreatetruecolor($cropCoords['targetImageWidth'], $cropCoords['targetImageHeight']);
		
		imagealphablending( $this->image, false ); // otherwise imagesetpixel breaks alpha channel
		imagesavealpha( $this->image, true );      // required to preserve png's alpha channel

		imagecopy (
            $this->image, $this->source,
            $cropCoords['targetLeft'], $cropCoords['targetTop'],  $cropCoords['cropLeft'], $cropCoords['cropTop'],
            $cropCoords['cropWidth'], $cropCoords['cropHeight']
        );
		return $this->returnResource();
	}
}
?>