<?php

require_once SHARED_PATH . 'classes/image_text/_init.php';

function smarty_modifier_image_text ($text, $styleName = null, $pixelRatio = null)
{
    $imageText = singleton::get('image_text');

    $pixelRatioConfig = leaf_get( 'properties', 'textBanner', 'pixelRatio' );

    if (empty( $pixelRatio ) && !empty( $pixelRatioConfig ))
    {
        $pixelRatio = $pixelRatioConfig;
    }

    if (empty( $pixelRatio ))
    {
        $pixelRatio = 1.0;
    }

    $result = $imageText->getImageFromText ($text, $styleName, $pixelRatio);
    return $result;

}

?>