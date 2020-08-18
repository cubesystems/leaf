<?


class banner_flash extends banner_type
{
    public $defaultVersion = 7;
    public $swfobjectVersion = 1;
    public $defaultBackgroundColor = '#FFFFFF';
    public $getFlashImage = array(
        'file' => 'classes/banner/get_flash.gif',
        'width' => '74',
        'height' => '26'
    );

    public $expressInstallUrl = '3rdpart/swfobject/expressinstall.swf';

    public $minExpresInstallArea = 62500; // 250x250px

    function preProcess($params)
    {
        if (
            (!isset($params['altFlashContent']))
            &&
            (isset($params['altImage']))
        )
        {
            $altParams = $params;
            $altParams['object'] = $params['altImage'];
            unset(
                $altParams['altImage'],
                $altParams['elementId'], $altParams['containerId'],
                $altParams['containerClass'], $altParams['containerTag']
            );

            $smarty = null;
            $altFlashContent = smarty_function_banner($altParams, $smarty);
            if ($altFlashContent)
            {
                $params['altFlashContent'] = $altFlashContent;
            }
        }


        if (!isset($params['containerId']))
        {
            $params['containerId'] = 'flashContainer_' . uniqid();
        }

        if (!isset($params['elementId']))
        {
            $params['elementId'] = 'flash_' . uniqid();
        }

        if (
            (!isset($params['width']))
            &&
            (isset($this->data['extra_info']))
            &&
            (isset($this->data['extra_info']['image_width']))
        )
        {
            $params['width'] = $this->data['extra_info']['image_width'];
        }

        if (
            (!isset($params['height']))
            &&
            (isset($this->data['extra_info']))
            &&
            (isset($this->data['extra_info']['image_height']))
        )
        {
            $params['height'] = $this->data['extra_info']['image_height'];
        }

        if (
            (!isset($params['version']))
            ||
            (!$params['version'])
        )
        {
			$globalFlashVersion = leaf_get('properties','flash','defaultFlashVersion');
            $params['version'] = (is_int($globalFlashVersion)) ? $globalFlashVersion : $this->defaultVersion;
        }

        if (leaf_get('properties','flash','defaultSwfobjectVersion'))
        {
            $this->swfobjectVersion = leaf_get('properties','flash','defaultSwfobjectVersion');
            $this->expressInstallUrl = '3rdpart/swfobject2/expressInstall.swf';
        }


		if( leaf_get('properties', 'flash', 'global_wmode') )
		{
			$params['params']['wmode'] = leaf_get('properties', 'flash', 'global_wmode');
		}

        if (
            (!isset($params['bgColor']))
        )
        {
            // $params['bgColor'] = $this->defaultBackgroundColor;
        }

        if (!isset($params['useExpressInstall']))
        {
            // decide default express install behaviour from flash size
            $area = $params['width'] * $params['height'];
            if ($area > $this->minExpresInstallArea)
            {
                $params['useExpressInstall'] = true;
            }
        }

        if (
            (isset($params['useExpressInstall']))
            &&
            ($params['useExpressInstall'])
        )
        {
            $params['expressInstallUrl'] = SHARED_WWW . $this->expressInstallUrl;
        }


        $params['getFlashImage'] = $this->getFlashImageData($params);


        //debug ($params);
        return $params;
    }

    function getFlashImageData( $params )
    {
        $flashWidth = $params['width'];
        $flashHeight = $params['height'];

        $iconWidth = $this->getFlashImage['width'];
        $iconHeight = $this->getFlashImage['height'];

        $widthDiff = $flashWidth - $iconWidth;

        $leftBorder = $rightBorder = 0;
        if ($widthDiff > 0)
        {
            $leftBorder = floor($widthDiff / 2);
            $rightBorder = $widthDiff - $leftBorder;
        }
        elseif ($widthDiff < 0)
        {
            $iconWidth = $flashWidth; // squeeze image to fit the size of the original flash
        }

        $heightDiff = $flashHeight - $iconHeight;
        $topBorder = $bottomBorder = 0;
        if ($heightDiff > 0)
        {
            $topBorder = floor($heightDiff / 2);
            $bottomBorder = $heightDiff - $topBorder;
        }
        elseif ($heightDiff < 0)
        {
            $iconHeight = $flashHeight; // squeeze image to fit the size of the original flash
        }

        $imageData = array
        (
            'file'          => SHARED_WWW . $this->getFlashImage['file'],
            'width'         => $iconWidth,
            'height'        => $iconHeight,

            'topBorder'     => $topBorder,
            'rightBorder'   => $rightBorder,
            'bottomBorder'  => $bottomBorder,
            'leftBorder'    => $leftBorder,

            'borderColor'   => (!empty($params['bgColor'])) ? $params['bgColor'] : $this->defaultBackgroundColor
        );

        return $imageData;
    }


}


?>