<?
class imageRotate extends imageBase
{

	public function processInput($sourceResource, $params = array())
	{
		parent::processInput($sourceResource, $params);

		if ($this->ok !== true)
		{
		    return false;
		}

		if (
            (empty($params['angle']))
            ||
            (!ispositiveint($params['angle']))
        )
        {
            $params['angle'] = 0;
        }

		if (
            (empty($params['backgroundColor']))
            ||
            (!ispositiveint($params['backgroundColor']))
        )
        {
            $params['backgroundColor'] = 0;
        }

        if (function_exists('imagerotate'))
        {
            $this->image = imagerotate( $this->source, $params['angle'], $params['backgroundColor'] );
        }
        else
        {
            $img = self::rotateImage( $this->source, $params['angle'] );
            if (!$img)
            {
                $img = null;
            }
            $this->image = $img;
        }
		return $this->returnResource();
	}

	/* copied from php.net imagerotate comments */
    protected static function rotateImage($img, $rotation) {
      $width = imagesx($img);
      $height = imagesy($img);
      switch($rotation) {
        case 90:  $newimg= @imagecreatetruecolor($height , $width );break;
        case 180: $newimg= @imagecreatetruecolor($width , $height );break;
        case 270: $newimg= @imagecreatetruecolor($height , $width );break;
        case 0: return $img;break;
        case 360: return $img;break;
      }
      if($newimg) {
        for($i = 0;$i < $width ; $i++) {
          for($j = 0;$j < $height ; $j++) {
            $reference = imagecolorat($img,$i,$j);
            switch($rotation) {
              case 270: if(!@imagesetpixel($newimg, ($height - 1) - $j, $i, $reference )){return false;}break;
              case 180: if(!@imagesetpixel($newimg, $width - $i, ($height - 1) - $j, $reference )){return false;}break;
              case 90: if(!@imagesetpixel($newimg, $j, $width - $i, $reference )){return false;}break;
            }
          }
        } return $newimg;
      }
      return false;
    }

}
?>