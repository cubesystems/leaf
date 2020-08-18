<?
class imageCrop extends imageBase{

	public function getCropCoords($params){
		$sourceWidth = imagesx($this->source);
		$sourceHeight = imagesy($this->source);

		if(!empty($params['width']) && empty($params['height']))
		{
			$params['height'] = $params['width'];
		}
		else if(!empty($params['height']) && empty($params['width']))
		{
			$params['width'] = $params['height'];
		}
		// calculate thumb coordinates

		// if image is larger (wider) than thumb size (width), position the thumb in the center (horizontally)
		// else use actual dimensions

		if ($sourceWidth > $params['width'])
		{
		    $diff = $sourceWidth - $params['width'];
		    $centerOffset = floor($diff / 2);
		    $left = $centerOffset;
		    $right = $centerOffset + $params['width'];
		}
		else
		{
		    $left = 0;
		    $right = $sourceWidth;
		}

		// repeat same for height
		if ($sourceHeight > $params['height'])
		{
		    $diff = $sourceHeight - $params['height'];
		    $centerOffset = floor($diff / 2);
		    $top = $centerOffset;
		    $bottom = $centerOffset + $params['height'];
		}
		else
		{
		    $top = 0;
		    $bottom = $sourceHeight;
		}

		return array(
			'top'    => $top,
			'left'   => $left,
			'width'  => $right - $left,
			'height' => $bottom - $top
		);
	}

	public function processInput($sourceResource, $params = array())
	{
		parent::processInput($sourceResource, $params);
		$cropCoords = $this->getCropCoords($params);
		$this->image = imagecreatetruecolor($cropCoords['width'], $cropCoords['height']);
		
		imagealphablending( $this->image, false ); // otherwise imagesetpixel breaks alpha channel
		imagesavealpha( $this->image, true );      // required to preserve png's alpha channel
		
		imagecopyresampled (
            $this->image, $this->source,
            0, 0, $cropCoords['left'], $cropCoords['top'],
            $cropCoords['width'], $cropCoords['height'], $cropCoords['width'], $cropCoords['height']
        );
		return $this->returnResource();
	}
}
?>