<?
class imageResizeAndCrop extends imageBase {

    public function getResizeParams( $params )
    {
		$sourceWidth = imagesx($this->source);
		$sourceHeight = imagesy($this->source);

        // find smallest resize ratio

        $ratioX = $sourceWidth / $params['width'];
        $ratioY = $sourceHeight / $params['height'];

        $smallestRatio = ($ratioX > $ratioY) ? $ratioY : $ratioX;


        // if smallest ratio is < 1
        // (source image is already smaller than target size at least in one dimension, skip resize)
        if ($smallestRatio < 1)
        {
            return false;
        }

        // use only one dimension for resizing
        $useWidth = ($ratioX > $ratioY) ? false : true;
        $useHeight = !$useWidth;

        $resizeParams = array();

        if ($useWidth)
        {
            $resizeParams['width'] = round( $sourceWidth / $smallestRatio);
        }

        if ($useHeight)
        {
            $resizeParams['height'] = round( $sourceHeight / $smallestRatio);
        }

        return $resizeParams;
    }

	public function processInput($sourceResource, $params = array())
	{
		parent::processInput($sourceResource, $params);

		$resizeParams = $this->getResizeParams($params);

		$cropSource = & $this->source;

		if ($resizeParams)
		{
			$resizePlugin = getObject('imageResize');
			$resizePlugin->setInputType('resource');

			$resizePlugin->setOutputType('resource');

			$resizedImage = $resizePlugin->processInput($this->source, $resizeParams);

			unset( $cropSource ) ; // destroy ref
            $cropSource = & $resizedImage;
            // debug ($cropSource, 0);
		}

		$cropPlugin = getObject('imageCrop');

		$cropPlugin->setInputType('resource');
		$cropPlugin->setOutputType('resource');

		// remove targetFile if set in params (otherwise it overrides the manually set output type)
		unset($params['targetFile']);

		$this->image = $cropPlugin->processInput($cropSource, $params);

		return $this->returnResource();
	}
}
?>
