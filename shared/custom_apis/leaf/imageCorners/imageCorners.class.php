<?
class imageCorners extends imageBase{
	
	public function processInput($sourceResource, $params = array())
	{
		parent::processInput($sourceResource, $params);
		if(empty($params['cornerFile']) || !file_exists($params['cornerFile']))
		{
			trigger_error('no corner file', E_USER_ERROR);
		}
		else
		{
			$cornerFile = $params['cornerFile'];
		}
		// convert jpeg to png
		if( !empty($params['transparentCorners']) )
		{
			$this->overrideFileFormat = 'png';
			$parts = explode( '.', $this->outputFilename );
			$parts[ count($parts) - 1 ] = 'png';
			$this->outputFilename = implode( '.', $parts );
        }

        // fallback for older sites
        if(!isset($params['modifyCorners']))
        {
            $params['modifyCorners'] = array('tl', 'tr', 'bl', 'br');
        }

		$topleft = in_array('tl', $params['modifyCorners']) ? true : false;
		$bottomleft = in_array('bl', $params['modifyCorners']) ? true : false;
		$bottomright = in_array('br', $params['modifyCorners']) ? true : false;
		$topright = in_array('tr', $params['modifyCorners']) ? true : false;

		$corner = imagecreatefrompng($cornerFile);
		$cornerWidth = imagesx($corner);
		$cornerHeight = imagesy($corner);
		$this->image = $this->source;
		$imageWidth = imagesx($this->image);
		$imageHeight = imagesy($this->image);
		// transparent corners
		if( !empty($params['transparentCorners']) )
		{
			// nokopēju izmērus. ņemu vērā, ka sākas no 0,0
			$w = $imageWidth - 1; 
			$h = $imageHeight - 1; 
			imagealphablending($this->image, false); // cit�di imagesetpixel sa�akar� alpha kan�lu
			imagesavealpha($this->image, true); // nepiecie�ams png alfa kan�la saglab��anai
			for( $y = 0; $y < $cornerHeight; $y++ ) // y cikls
			{
				for($x = 0; $x < $cornerWidth; $x++) // x cikls
				{ 
					$x2 = $x; $y2 = $y; // lai nenoboj�tu main�gos
					// iegūstu alpha
					$templateColor = imagecolorat($corner, $x2, $y2);
					$templateColorIndex = imagecolorsforindex($corner, $templateColor);
                    
                    // nosaka stūru skaitu kuriem nepieciešams uzlikt masku
                    $cornersCount = (!empty($params['modifyCorners'])) ? sizeof($params['modifyCorners']) : 4;
                    
					for ( $i = 0; $i < $cornersCount; $i++ ) // darbības izpildu 4 att�la st�riem
					{
                        switch ( $params['modifyCorners'][$i] )
						{
							case 'tr': // labais augšējais ststūrisris
                                if ($topright == true)
                                {
                                    $x2 = $w - $x;
                                    $y2 = $y;
                                }
							break;
							case 'br': // labais apakšējais stūris
                                if ($bottomright == true)
                                {
                                    $x2 = $w - $x;
                                    $y2 = $h - $y;
                                }
							break;								
							case 'bl': // kreisais apakšējais stūris
                                if ($bottomleft == true)
                                {
                                    $x2 = $x;
                                    $y2 = $h - $y;
                                }
							break;
						}
                        
						$color = imagecolorat($this->image, $x2, $y2);
						$colorIndex = imagecolorsforindex($this->image, $color);
						$colorIndex['alpha'] = $colorIndex['alpha'] + $templateColorIndex['alpha'];
						if ( $colorIndex['alpha'] > 127)
						{
							$colorIndex['alpha'] = 127;
						}
						$color = imagecolorallocatealpha( $this->image, $colorIndex['red'], $colorIndex['green'], $colorIndex['blue'], $colorIndex['alpha']);
						imagesetpixel($this->image, $x2, $y2, $color);
						// nomainu att�la st�ri						
					}
				} // end for x
			} // end for y
		}
		// classic corners
		else
		{
			// Top-left corner
			if ($topleft == true)
			{
				$destX = 0;
				$destY = 0;
				imagecopy($this->image, $corner, $destX, $destY, 0, 0, $cornerWidth, $cornerHeight);
			} 
			// Bottom-left corner
			if ($bottomleft == true)
			{
				$destX = 0; 
				$destY = $imageHeight - $cornerHeight; 
				$rotated = imagerotate($corner, 90, 0);
				imagecopy($this->image, $rotated, $destX, $destY, 0, 0, $cornerWidth, $cornerHeight);
			}
			// Bottom-right corner
			if ($bottomright == true)
			{
				$destX = $imageWidth - $cornerWidth;
				$destY = $imageHeight - $cornerHeight;
				$rotated = imagerotate($corner, 180, 0);
				imagecopy($this->image, $rotated, $destX, $destY, 0, 0, $cornerWidth, $cornerHeight);
			}
			// Top-right corner
			if ($topright == true)
			{
				$destX = $imageWidth - $cornerWidth;
				$destY = 0;
				$rotated = imagerotate($corner, 270, 0);
				imagecopy($this->image, $rotated, $destX, $destY, 0, 0, $cornerWidth, $cornerHeight);
			}
		}
		imagedestroy($corner);
		return $this->returnResource();
	}
}
?>
