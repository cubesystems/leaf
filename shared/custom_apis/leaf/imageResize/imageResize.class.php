<?
class imageResize extends imageBase{

	public function getSizes($params){
		//get sizes
		$sourceWidth = imagesx($this->source);
		$sourceHeight = imagesy($this->source);
		// calculate target sizes
		// find which dimension of the source has the largest ratio against the given limits
		// width:
		if (
			(!empty($params['width']))
			&&
			($sourceWidth > $params['width'])
		)
		{
			$widthRatio = $sourceWidth / $params['width'];
		}
		else
		{
			$widthRatio = 1; // no need to resize
		}

		// height:
		if (
			(!empty($params['height']))
			&&
			($sourceHeight > $params['height'])
		)
		{
			$heightRatio = $sourceHeight / $params['height'];
		}
		else
		{
			$heightRatio = 1; // no need to resize
		}

		// combined:
		if ($widthRatio > $heightRatio)
		{
            $ratio = $widthRatio;

            $targetWidth = ($widthRatio > 1) ? $params['width'] : $sourceWidth;
		    $targetHeight = round($sourceHeight / $ratio);
		}
		elseif ($widthRatio < $heightRatio)
		{
            $ratio = $heightRatio;

		    $targetWidth  = round($sourceWidth / $ratio);
		    $targetHeight = ($heightRatio > 1) ? $params['height'] : $sourceHeight;
		}
		else // equal ratios
		{
		    $ratio = $heightRatio;

		    $targetWidth  = ($ratio > 1) ? $params['width']  : $sourceWidth;
		    $targetHeight = ($ratio > 1) ? $params['height'] : $sourceHeight;
		}

		/*
		$targetWidth  = round($sourceWidth / $ratio);
		$targetHeight = round($sourceHeight / $ratio);
		*/

		return array(
			'sourceWidth' => $sourceWidth,
			'sourceHeight' => $sourceHeight,
			'width' => $targetWidth,
			'height' => $targetHeight
		);
	}

	public function processInput($sourceResource, $params = array())
	{
		parent::processInput($sourceResource, $params);
		if($this->ok === true)
		{
			// set target
			$imageSize = $this->getSizes($params);

			$this->image = imagecreatetruecolor($imageSize['width'], $imageSize['height']);

			imagealphablending( $this->image, false ); // otherwise imagesetpixel breaks alpha channel
			imagesavealpha( $this->image, true );      // required to preserve png's alpha channel

			imagecopyresampled($this->image, $this->source, 0, 0, 0, 0, $imageSize['width'], $imageSize['height'], $imageSize['sourceWidth'], $imageSize['sourceHeight']);

			if (!empty($params['forceExactSize']))
			{
			    // create another image with the exact needed size and position the resized image in the center
			    $forceWidth  = (int) ($params['width']);
			    $forceHeight = (int) ($params['height']);

			    if (
                    ($imageSize['width'] != $forceWidth)
                    ||
                    ($imageSize['height'] != $forceHeight)
                )
                {

                    $exactImage = imagecreatetruecolor($forceWidth, $forceHeight);
        			imagealphablending( $exactImage, false ); // otherwise imagesetpixel breaks alpha channel
			        imagesavealpha( $exactImage, true );      // required to preserve png's alpha channel

			        $transparent = imagecolorallocatealpha($exactImage, 0, 0, 0, 127);
			        imagefill($exactImage, 0, 0, $transparent);

			        $xPos = floor( ($forceWidth  - $imageSize['width']) / 2 );
			        $yPos = floor( ($forceHeight - $imageSize['height']) / 2 );

                    imagecopy ( $exactImage, $this->image, $xPos  , $yPos , 0  , 0  , $imageSize['width'] ,$imageSize['height'] );
                    imagedestroy ($this->image);
                    $this->image = $exactImage;

                }
			}


			return $this->returnResource();
		}
		else
		{
			return false;
		}
	}

}
?>