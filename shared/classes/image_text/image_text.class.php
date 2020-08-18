<?
/*
    notes and todos:

    *) add support for variously positioned and tiled background images
       this should be more flexible than CSS backgrounds

*/

/*
available style attributes:

    $style = array(
        // REQUIRED:

        'font-name'             => 'MyriadPro-Regular',  // string
        'font-type'             => 'otf',                // one of $fontTypes (otf/ttf). defaults to $fontTypes[0]
        'font-size'             => '20',                 // int, in px
        'color'                 => '#FEDCBA',            // #RRGGBB or 'transparent'


        // OPTIONAL:
        //'line-height'           => '15',               // int, in px, defaults to font-size
                                                         // 'normal' to reset
        //'text-align'            => 'left',             // one of $textAligns, defaults to $textAligns[0]

        //'background-color'      => '#123456',          // #RRGGBB or 'transparent', defaults to transparent
                                                         // or 'transparent #RRGGBB' for background context

        // 'background-context-color' => '#123456',      // background context color to use with transparent backgrounds
                                                         // for gif output (the color of the rough edges)

        //'line-offset'           => 0,                  // int, defaults to round($style['font-size'] / 5) * -1;
                                                         // moves text of each line n pixels down ( use negative to move up)
                                                         // to compensate baseline/bottom differences
        //'extra-line-space'      => 5,                  // int, defaults to 0
                                                         // moves each next line n pixels lower than its
                                                         // default position

        //'padding-top'           => 100,                // int, defaults to 0
        //'padding-right'         => 0,                  // int, defaults to 0
        //'padding-bottom'        => 0,                  // int, defaults to 0
        //'padding-left'          => 0,                  // int, defaults to 0

        // alternate shorthand syntax:
        //'padding'               => '10 40',
        //'padding'               => '5 5',
        //'padding'               => '5 5 5 5',

        //'width'                 => null,               // int, in px, defaults to NULL
        //'height'                => null,               // int, in px, defaults to NULL
                                                         // 'auto' to reset
                                                         // these force exact image dimensions
                                                         // regardless of the size of the text box
                                                         // these are ignored if set to NULL

        //'max-width'             => null,               // int, in px, defaults to NULL
                                                         // 'none' to reset
                                                         // defines maximum width of image (including padding)
                                                         // if this is set and > 0, the text will be automatically
                                                         // wrapped to multiple lines if needed

		//'min-width'			  => null, 				 // int, in px, defaults to NULL
		                                                 // 'none' or 0 to reset
		                                                 // defines minimum text box width (padding not included)
														 // max-width takes priority over min-width

        //'min-height'            => null                // int, in px, defaults to NULL
                                                         // 'none' or 0 to reset
		                                                 // defines minimum text box height (padding not included)

        //'string-split'          => '|',                // string, defaults to NULL
                                                         // if not NULL, splits text into multiple lines
                                                         // using this string as delimiter

        //'image-type'            => 'gif',              // one of $imageTypes, defaults to $imageTypes[0]
                                                            or png if transparency is set

        //'angle'                 => 0,                  // int, defaults to 0
                                                         // NOT IMPLEMENTED

                                                         // these allow to do a preg_replace on text before output
        //'replace-from'          => ''                  // string, regexp search
        //'replace-to'            => ''                  // string, regexp replace

        //'add-spaces'            => 1                   // int, defaults to 0
                                                         // inserts n spaces after every character in string

        //'underline-position'    .....                  // deprecated, use text-decoration

        //'text-transform'        => 'uppercase'         // transforms text string. one of $textTransforms.
                                                         // defaults to $textTransforms[0] ('none');

        //'text-decoration'       => 'underline'         // css text decorations.
                                                         // must be one of $textDecorations.
                                                         // defaults to $textDecorations[0] ('none');
                                                         // does not work together with angle and linear gradient

        // optional line parameters for text-decoration underline:

        //'line-position'         => 15                  // int, in px, relative to the top of each line box
        //'line-thickness'        => 2                   // int, in px
        //'line-color'            => null                // if not specified, uses font color


        //'background-image'      => 'images/foo.gif'    // main background image
                                                            relative to PATH constant

        //'background-position'   => 'left top'          // position of main background image
                                                            both dimensions accept exact values in px
                                                            x position accepts keywords : left center right
                                                            y position accepts keywords : top center bottom

        //'background-repeat'     => 'repeat'            // repeat | repeat-x | repeat-y | no-repeat

        //'background-image-left-top' => 'images/corner.gif'
        //'background-image-left-bottom'                 // additional optional non-repeating background images
        //'background-image-right-top'                   // for use in corners
        //'background-image-right-bottom'

        //'shadow-color'          => '#123456'           // color of the shadow text
        //'shadow-offset-x'       => -3                  // int, defaults to 0, offset in px from the main text
        //'shadow-offset-y'       => 3                   // int, defaults to 0, offset in px from the main text

		//'outline-color' 		  => '#ffffff' 			 // color of 1px outline, can be in "rgba(0,0,0,.5)" form

		//'linear-gradient'		  => 					 //	<linear-gradient> := [ <angle>, ]? <color-stop>, <color-stop>[, <color-stop>]*
		  												 //	<color-stop> := <color> [ <percentage> ]?
    );
*/

class image_text extends singleton_object
{
    // const
    var $config = array(
        'imageCacheEnabled' => null,
        'styleCacheFile'    => null,
        'styleConfigFile'   => null,
        'saveImageInfo'     => null
    );

    var $styles = array(); // will hold processed styles

    protected $fontTypes  = array('ttf', 'otf');
    protected $textAligns = array('left', 'right', 'center');
    protected $verticalAligns = array('top', 'bottom', 'middle');
    protected $imageTypes = array('gif', 'png', 'jpeg');
    protected $textTransforms  = array('none', 'uppercase', 'lowercase');
    protected $textDecorations = array('none', 'underline');

    var $configDir  = null;
    var $fontDir    = null;
    var $cacheDir   = null;
    var $cacheWww   = null;

    var $freeTypeVersion = null;

    var $styleFile  = null;
    var $styleCacheFile = null;
    var $rawStyles = null;

    protected $inheritanceStack = array();

    var $imageInfoCache = array();

    protected $lineBoxCache = array();
    protected $infiniteLoopProtection = 500;

    function image_text()
    {
        parent::singleton_object();

        $this->configDir    = PATH . 'config/';
        $this->fontDir      = SHARED_PATH . 'fonts/';
        $this->cacheDir     = CACHE_PATH;
        $this->cacheWww     = CACHE_WWW;

        $this->loadConfig();
        $this->detectFreeTypeVersion();
        $this->styleFile = $this->configDir . $this->config['styleConfigFile'];
        $this->styleCacheFile = $this->cacheDir . $this->config['styleCacheFile'];
        $this->loadStyles();
    }

    function loadConfig()
    {
        // loads config file
        $config = array();
        require_once $this->configDir . 'image_text_config.php';
        if (!is_array($config))
        {
            return false;
        }
        foreach ($config as $key => $value)
        {
            if (!key_exists($key, $this->config))
            {
                continue;
            }

            $this->config[$key] = $config[$key];
        }
        return true;
    }



    function loadStyles()
    {
        if (!is_readable($this->styleFile))
        {
            $this->error('Style definitions file not readable: "' . $this->styleFile . '".');
        }

        if ($this->loadStylesFromCache())
        {
            // loaded from cache, all ok
            return true;
        }

        // not loaded from cache.
        // need to process the stylesheet config file

        $styles = css_syntax_parser::parseFile ( $this->styleFile );

        if (!is_array($styles))
        {
            return false;
        }

        $this->rawStyles = array();
        foreach ($styles as $styleName => $style)
        {
            $this->rawStyles[$styleName] = $style;
        }

        $this->styles = array(); // clean any previous style data
        foreach ($this->rawStyles as $styleName => $styleValue)
        {
            $compileOk = $this->compileStyle($styleName);
        }

        $this->writeStylesToCache();

        return true;
    }

    function loadStylesFromCache()
    {
        if (!is_readable($this->styleCacheFile))
        {
            return false;
        }

        $cachedStyles = file_get_contents($this->styleCacheFile);
        $cachedStyles = unserialize($cachedStyles);
        if (!$cachedStyles)
        {
            return false;
        }

        $currentMtime = filemtime($this->styleFile);
        $lastMtime = $cachedStyles['stylesheetMTime'];

        if ($currentMtime <= $lastMtime)
        {
            // use cached
            $this->rawStyles = $cachedStyles['rawStyles'];
            $this->styles = $cachedStyles['styles'];
            return true;
        }
        return false;
    }

    function writeStylesToCache()
    {
        $cacheContent = array(
            'stylesheetMTime' => filemtime( $this->styleFile ),
            'rawStyles'       => $this->rawStyles,
            'styles'          => $this->styles
        );
        $cacheString = serialize($cacheContent);

    	$handle = fopen($this->styleCacheFile, 'w');
    	fwrite($handle, $cacheString);
    	fclose($handle);

        return true;
    }


    function getImageCacheFileName($text, $styleKey, $pixelRatio)
    {
        if (!$styleKey)
        {
            $styleKey = null;
        }

        // load style
        if (!$style = $this->getStyle($styleKey, $pixelRatio))
        {
            return null;
        }

        $styleCode = serialize($style);
        $hash = md5 ($styleCode . $text . md5( $pixelRatio ));

        $cacheFileName = $hash . '.' . $style['image-type'];
        return $cacheFileName;

    }

    // main function. returns url to generated image or null on error
    function getImageFromText($text, $className = null, $pixelRatio = 1.0, $filenameOnly = null, $additionalStyleDef = null)
    {

        
        if (!$className)
        {
            $className = null;
        }

        // load style
        if (!$style = $this->getStyle($className, $pixelRatio, $additionalStyleDef))
        {
            return null;
        }

        if (isset($style['style-key']))
        {
            $styleKey = $style['style-key'];
        }
        else
        {
            $styleKey = $className;
        }


        // calculate cache filename

        $cacheFileName = $this->getImageCacheFileName( $text, $styleKey, $pixelRatio );


        
        $cacheFileLocal  = $this->cacheDir . $cacheFileName;
        $cacheFileRemote = $this->cacheWww . $cacheFileName;

        if (
            (!$this->config['imageCacheEnabled'])  // caching disabled, regenerate every time
            ||
            (!is_file($cacheFileLocal))  // not cached
        )
    	{
            $imageGeneratedOk = $this->generateImage($text, $style, $this->cacheDir, $cacheFileName);

    	}

    	if ($filenameOnly)
    	{
    	    return $cacheFileName;
    	}
    	else
    	{
    	    return $cacheFileRemote;
    	}

    }

    function getStyle($classNameOrStyleKey, $pixelRatio = 1.0, $additionalStyleDef = null)
    {
        $styleKey = $classNameOrStyleKey;

        if (!empty($additionalStyleDef))
        {
            $styleKeySuffix = $additionalStyleDef;
            if (!is_string($additionalStyleDef))
            {
                $styleKeySuffix = serialize( $styleKeySuffix );
            }
            $styleKeySuffix = sha1( $styleKeySuffix );
            $styleKey .= '_' . $styleKeySuffix;
        }


        $styleOk = true;
        if (!isset($this->styles[ $styleKey ]) || !$this->config['imageCacheEnabled'])
        {
            $styleOk = false;

            // style not yet compiled, attempt to compile it
            $additionalRules = null;
            if (!empty($additionalStyleDef))
            {
                if (!is_array($additionalStyleDef))
                {
                    $additionalStyleDef = css_syntax_parser::getRules( $additionalStyleDef );
                }
                $additionalRules = $additionalStyleDef;
                if (empty($additionalRules))
                {
                    $additionalRules = null;
                }
            }
            $compileOk = $this->compileStyle($classNameOrStyleKey, $additionalRules, $styleKey);
            $styleOk = $compileOk;
        }



        if ($styleOk)
        {
            return $this->processPixelRatio( $styleKey, $pixelRatio );
        }

        $this->error('Text class not defined: "' . $styleKey . '".');

        return null;
    }

    function processPixelRatio( $styleKey, $pixelRatio )
    {
        $map = array
        (
            'font-size',
            'font-size-px',
            'font-size-pt',
            'extra-line-space',
            'padding-top',
            'padding-right',
            'padding-bottom',
            'padding-left',
            'shadow-offset-x',
            'shadow-offset-y',
            'line-height',
            'width',
            'height',
            'max-width',
            'min-width',
            'min-height',
            'line-thickness',
            'line-position',
            'line-offset',
        );

        $style = $this->styles[ $styleKey ];
        
        foreach ( $map as $key )
        {

            if (!empty( $style[$key] ))
            {
                if ( 'font-size-pt' == $key )
                {
                    $style[$key] = round( $style['font-size-px'] * 0.75 );
                }
                else
                {
                    $style[$key] = round( $style[$key] * $pixelRatio );
                }
            }

        }

        $mapImages = array
        (
            'background-image',
            'background-image-left-top',
            'background-image-left-bottom',
            'background-image-right-top',
            'background-image-right-bottom',
        );

        $mask = '-' . $pixelRatio;

        foreach ( $mapImages as $key )
        {

            if (!empty( $style[$key . $mask] ))
            // there IS defined image [..] @{pixelRatio}x
            {
                $style[$key] = $style[$key . $mask];
            }
            elseif (!empty( $style[$key] ))
            // there is NOT defined image [..] @{pixelRatio}x
            // so check, there exists image without definitio
            {
                $filename = $this->getPixelRatioImagePath( $pixelRatio, $style[$key] );
                if ( file_exists( PATH . $filename ))
                {
                    $style[$key] = $filename;
                }
                else
                {
                    if( strpos('.', $pixelRatio ) === false )
                    {
                        $filename = $this->getPixelRatioImagePath( $pixelRatio . '.0', $style[$key] );
                        if ( file_exists( PATH . $filename ))
                        {
                            $style[$key] = $filename;
                        }
                    }
                }
            }

        }

        return $style;
    }

    function getPixelRatioImagePath( $pixelRatio, $value )
    {
        $path = preg_replace( '/(\t|\"|\'|\(|\)|url|\s)/', '', $value );
        $dotPos  = strrpos( $path, '.' );
        $newFile = substr( $path, 0, $dotPos ) . '-@' . $pixelRatio . 'x' . substr( $path, $dotPos );
        return $newFile;
    }

    function compileStyle($className, $additionalRules = null, $styleKey = null)
    {

        $style = $this->getRawStyle( $className );
        if (!$style)
        {
            return false;
        }

        if (is_null($styleKey))
        {
            $styleKey = $className;
        }


        // add additional rules
        if (
            (!empty($additionalRules))
            &&
            (is_array($additionalRules))
        )
        {
            foreach ($additionalRules as $key => $value)
            {
                if ($key == 'extends')
                {
                    continue;
                }
                $style[$key] = $value;
            }
        }

        unset( $style['extends'] ); // remove 'extends' attribute in any case

        $requiredParams = array('font-name', 'font-size', 'color');
        foreach ($requiredParams as $param)
        {
            if (!key_exists($param, $style))
            {
                $this->error($param . ' not defined for text class "' . $className . '".');
            }
        }


        if (
            (isset($style['font-type']))
            &&
            (in_array($style['font-type'], $this->fontTypes))
        )
        {
            $fontType = $style['font-type'];
        }
        else
        {
            reset ($this->fontTypes);
            $fontType = current($this->fontTypes);
        }
        $style['font-file'] = $style['font-name'] . '.' . $fontType;

        if (
            (!file_exists($this->fontDir . $style['font-file']))
            ||
            (!is_readable($this->fontDir . $style['font-file']))
        )
        {

            $this->error('Font file not found: "' . $style['font-name'] . '.' . $fontType . '".');
        }

        // validate font-size
        if (
            (!$style['font-size'] = (int) $style['font-size'])
            ||
            ($style['font-size'] < 0)
        )
        {
            $this->error('Invalid font-size: "' . $style['font-size'] . '".');
        }
        // calculate font-sizes
        unset($style['font-size-px'], $style['font-size-pt']); // unset these if someone has set them before
        $style['font-size-px'] = $style['font-size'];
        $style['font-size-pt'] = round($style['font-size'] * 0.75);

        // validate color (hex2rgb dies on error)
        $color = $this->hex2rgb($style['color']);
        // shadow color will be validated later
		/* $shadowColor = null;
        if (!empty($style['shadow-color']))
        {
            $shadowColor = $this->hex2rgb($style['shadow-color']);
        } */

		// $outlineColor = null; // outline color will be validated later

        // validate optional params, set to defaults if not set
        // shorthand values
        if (isset($style['padding']))  // if set
        {
            // parse combined values
            $padding = $this->parseShorthandValue($style['padding']);
            //debug( $padding);
            if (is_array($padding))
            {
                $map = array(
                    0 => 'top',
                    1 => 'right',
                    2 => 'bottom',
                    3 => 'left'
                );
                foreach ($map as $key => $val)
                {
                    if (isset($style['padding-' . $val]))
                    {
                        continue;
                    }
                    $style['padding-' . $val] = $padding[$key];
                }
            }

        }

        // allowed value list params
        $validateListParams = array(
            'text-align'        => $this->textAligns,
            'vertical-align'    => $this->verticalAligns,
            'text-transform'    => $this->textTransforms,
            'text-decoration'   => $this->textDecorations
        );

        foreach ($validateListParams as $param => $list)
        {
            if (
                (!isset($style[$param]))
                ||
                (!in_array($style[$param], $list))
            )
            {
                $style[$param] = $list[0];
            }
        }


        // int params and their default values
        $validateIntParams = array
        (
            'extra-line-space'      => 0,
            'padding-top'           => 0,
            'padding-right'         => 0,
            'padding-bottom'        => 0,
            'padding-left'          => 0,
            'shadow-offset-x'       => 0,
            'shadow-offset-y'       => 0,
            'angle'                 => 0,
            'line-height'           => $style['font-size'],
            'width'                 => NULL,
            'height'                => NULL,
            'max-width'             => NULL,
			'min-width'             => NULL,
			'min-height'            => NULL,
            'transparency'          => false,
            'add-spaces'            => 0,
            'line-thickness'        => 1,
            'line-position'         => 0

        );

        if ($style['text-decoration'] == 'underline')
        {
            // calculate default line position and thickness only if needed
            $validateIntParams['line-thickness'] = $this->getDefaultLineThickness( $style['font-size'] );
            $validateIntParams['line-position']  = $this->getDefaultLinePosition( $style['font-size'] );
        }

        foreach ($validateIntParams as $param => $defaultValue)
        {
            if (
                (!isset($style[$param]))  // if not set
                &&
                ($style[$param] = $defaultValue)  // assign default, expression evaluates to false if default is 0 or NULL
            )
            {
                $style[$param] = (int) $style[$param];
            }

        }


        $defaultValues = $validateIntParams;

        $resetValues = array
        (
            'height'           => 'auto',
            'width'            => 'auto',
            'max-width'        => 'none',
            'min-width'        => 'none',
            'min-height'       => 'none',
            'line-height'      => 'normal',
            'line-thickness'   => 'normal',
            'line-position'    => 'auto'
        );

        foreach ($resetValues as $param => $value)
        {
            if (!isset($style[$param]))
            {
                continue;
            }

            if (strtolower($style[$param]) == $value)
            {
                $style[$param] = $defaultValues[ $param ]; // default value
            }
        }



        // other params
        if (!isset($style['line-offset']))  // if not set
        {
            $style['line-offset'] = round($style['font-size'] / 5) * -1;
        }
        else
        {
            $style['line-offset'] = (int) $style['line-offset'];
        }


        if (
            (!isset($style['string-split']))  // if not set
            ||
            (!is_string($style['string-split']))  // assign default, expression evaluates to false if default is 0 or NULL
        )
        {
            $style['string-split'] = null;
        }

        // other params
        if (
            (!isset($style['replace-from']))  // if not set
            ||
            (!is_string($style['replace-from']))
            ||
            (strlen($style['replace-from'])<1)
        )
        {
            $style['replace-from']  = null;
            $style['replace-to']    = null;
        }
        if (
            (!isset($style['replace-to']))  // if not set
            ||
            (!is_string($style['replace-to']))
            ||
            (strlen($style['replace-to'])<1)
        )
        {
            $style['replace-to'] = '';
        }
        if (
            (!isset($style['text-transform']))  // if not set
            ||
            (!is_string($style['text-transform']))
            ||
            (strlen($style['text-transform'])<1)
            ||
            ($style['text-transform'] == 'none')
        )
        {
            $style['text-transform']  = null;
        }
        if (
            (!isset($style['text-decoration']))  // if not set
            ||
            (!is_string($style['text-decoration']))
            ||
            (strlen($style['text-decoration'])<1)
            ||
            ($style['text-decoration'] == 'none')
        )
        {
            $style['text-decoration']  = null;
        }

        if (
            (!isset($style['background-color']))  // not set
            ||
            (!$style['background-color'] = strtolower($style['background-color']))  // empty string
            ||
            (!preg_match('/(transparent)?\s?(^#[a-f0-9]{6})?$/', $style['background-color'])) // is not in #RRGGBB format
        )
        {
            $style['background-color'] = 'transparent';
        }


        if (
            (!isset($style['line-color']))  // not set
            ||
            (!$style['line-color'] = strtolower($style['line-color']))
            ||
            (!preg_match('/(transparent)?\s?(^#[a-f0-9]{6})?$/', $style['line-color'])) // is not in #RRGGBB format
        )
        {
            $style['line-color'] = $style['color'];
        }



        $split = preg_split('/\s+/', $style['background-color'], null);
        if (
            ($split[0] == 'transparent')
            &&
            (!empty($split[1]))
        )
        {
            $style['background-color']         = 'transparent';
            $style['background-context-color'] = $split[1];
        }


        if ($style['background-color'] == 'transparent')
        {

            if (isset($style['background-context-color']))
            {
                $style['background-color'] = $style['background-context-color'];
            }
            else
            {
                $style['background-color'] = $style['color'];
            }
            $style['transparency'] = true;
        }

        $angleRExp = '(?P<value>[+\-]?([0-9]{1}|[1-9]\d+)?(\.\d+)?)(?P<unit>deg|rad|grad|turn)?';

        if (isset($style['linear-gradient']))
        {
			$colorStopRExp = '#(?P<color>[0-9a-fA-F]{6})(?: (?P<position>0|1?[0-9]{2})%)?';

        	$parts = explode(', ', $style['linear-gradient']);
			$angle = $parts[0];
			$matches = array ();
			if (preg_match('/^' . $angleRExp . '$/', $angle, $matches))
			{
				// Angle defined
				array_shift($parts);
				switch (get($matches, 'unit', 'deg'))
				{
					case 'deg':
						$angle = $matches['value'];
						break;
					case 'rad':
						$angle = rad2deg($matches['value']);
						break;
					case 'grad':
						$angle = ($matches['value'] / 400) * 360;
						break;
					case 'turn':
						$angle = $matches['value'] * 360;
						break;
				}
				$angle %= 360;
				$angle = ($angle < 0) ? 360 + $angle : $angle;
			}
			else
			{
				// Default angle
				$angle = 270.0;
			}
			$gradientParameters = array (
				'angle' => $angle,
				'colorStops' => array (),
			);

			if (!empty($parts))
			{
				$colorStopList = array ();
				do
				{
					$matches = array ();
					$colorStop = array_shift($parts);
					if (preg_match('/^' . $colorStopRExp . '$/', $colorStop, $matches))
					{
						$colorStopList[] = array (
							'color' => $matches['color'],
							'position' => get($matches, 'position', null),
						);
					}
				}
				while (!empty($parts));

				$firstIndex = 0;
				switch ($colorStopList[$firstIndex]['position'])
				{
					case '0':
						break;
					case null:
						$colorStopList[$firstIndex]['position'] = '0';
						break;
					default:
						array_unshift($colorStopList, array (
							'color' => $colorStopList[$firstIndex]['color'],
							'position' => '0',
						));
						break;
				}
				$lastIndex = sizeof($colorStopList) - 1;
				switch ($colorStopList[$lastIndex]['position'])
				{
					case '100':
						break;
					case null:
						$colorStopList[$lastIndex]['position'] = '100';
						break;
					default:
						array_push($colorStopList, array (
							'color' => $colorStopList[$lastIndex]['color'],
							'position' => '100',
						));
						break;
				}
				foreach ($colorStopList as $index => $colorStop)
				{
					if (is_null($colorStop['position']))
					{
						$previousPosition = $colorStopList[$index - 1]['position'];
						for ($i = $index, $c = 1; $i <= $lastIndex; $i++, $c++)
						{
							$lastPosition = $colorStopList[$i]['position'];
							if (!is_null($lastPosition)) break;
						}
						$positionOffset = round(($lastPosition - $previousPosition) / $c);
						$colorStopList[$index]['position'] = (string) ($previousPosition + $positionOffset);
					}
				}
				$gradientParameters['colorStops'] = $colorStopList;
			}

			if (isset($gradientParameters))
			{
				$style['linear-gradient'] = $gradientParameters;
			}
			else
			{
				unset($style['linear-gradient']);
			}
        }

        // image type. depends on transparency by default
        if (
            ($style['transparency'])
            &&
            (!isset($style['image-type']))
        )
        {
            $style['image-type'] = 'png'; // force PNG for transparent backgrounds if image-type not explicitly set
        }
        elseif (
            (isset($style['image-type']))
            &&
            ($style['image-type'] == 'jpg')
        )
        {
            $style['image-type'] = 'jpeg';
        }

        // if still not set, default to first in list
        if (
            (!isset($style['image-type']))
            ||
            (!in_array($style['image-type'], $this->imageTypes))
        )
        {
            $style['image-type'] = $this->imageTypes[0];
        }

        $style['style-key'] = $styleKey;


        $this->styles[ $styleKey ] = $style;
        if (!empty($additionalRules))
        {
            // a style with additional rules has been compiled,
            // write all styles to disk cache
            $this->writeStylesToCache();
        }
        return true;
    }

    function getTextWidth( $text, $style)
    {
        $lineBox = $this->getLineBox( $style['font-size-pt'], $style['angle'], $style['font-file'], $text, $style['line-height'] );
        return $lineBox['width'];
    }

    function getLineBox( $fontSize, $angle, $fontFile, $text, $lineHeight)
    {
        $fullFontPath = $this->fontDir . $fontFile;
        $lineBoxKey = $fontSize . '|' . $angle . '|' . $fullFontPath . '|' . $text . '|' . $lineHeight;
        $lineBoxKey = md5( $lineBoxKey );

        if (empty($this->lineBoxCache[$lineBoxKey]))
        {
            $lineBox = $this->getBBox($fontSize, $angle, $fullFontPath, $text);
            $lineBox['height']      = $lineBox[1] + abs($lineBox[5]);
            $lineBox['width']       = abs($lineBox[4] - $lineBox[0]);
            $lineBox['line-height'] = $lineHeight;

            $this->lineBoxCache[$lineBoxKey] = $lineBox;
        }

        return $this->lineBoxCache[$lineBoxKey];
    }

    function wrapLines ($lines, $style)
    {
        // iterate through all lines and check if they all fit in the given maxwidth for text
        // if a line does not fit, attempt to break it into several lines
        // if it still fails, leave line as is

        // returns modified $lines array

        // get maximum allowed width for text
        $maxTextWidth = $this->getMaxTextWidth( $style );
        if (!$maxTextWidth)
        {
            // failed to get max text width, abort
            return $lines;
        }

        // set word delimiter
        $wordDelimiter = ' ';
        if (!empty($style['add-spaces']))
        {
            // if add-spaces is set, words must be split on multiple spaces instead of one
            $addedSpaces = str_repeat($wordDelimiter, $style['add-spaces']);
            $wordDelimiter = $addedSpaces . $wordDelimiter . $addedSpaces;
        }

        $wrappedLines = array();
        foreach ($lines as $line)
        {
            // check if line fits
            $lineWidth = $this->getTextWidth( $line, $style );
            if ($lineWidth <= $maxTextWidth)
            {
                // line ok, leave as is, continue to next line
                $wrappedLines[] = $line;
                continue;
            }

            // line too long, split in parts
            $words = mb_split( $wordDelimiter, $line);


            // set line to an empty string
            // start adding words back to the line one by one.
            // when the box needed for the line exceeds maxtextwidth,
            // store line without the last word and start a new empty line
            $newLine = '';
            foreach ($words as $word)
            {
                $word = trim($word);
                $testLine = trim($newLine . $wordDelimiter . $word);

                $lineWidth = $this->getTextWidth( $testLine, $style);

                if ($lineWidth > $maxTextWidth)
                {
                    // line will be too long if the current word is added.
                    if (!empty($newLine)) // if this is not the first word (preventing infinte loop if the first word is already too long)
                    {
                        // store current line as finished, start a new line
                        $wrappedLines[] = $newLine;
                        $newLine = '';
                        $testLine = $word;
                    }

                }


                // line fits, add current word, continue loop
                $newLine = $testLine;
                continue;
            }

            if (!empty($newLine))
            {
                $wrappedLines[] = $newLine; // add last line
            }

        }
        return $wrappedLines;
    }

    function getMaxTextWidth( $style )
    {
        if (empty($style['max-width']))
        {
            return null;
        }
        $maxImageWidth = intval($style['max-width']);
        $padL = intval($style['padding-left']);
        $padR = intval($style['padding-right']);

        $maxTextWidth = $maxImageWidth - $padL - $padR;
        if ($maxTextWidth < 1)
        {
            return null;
        }
        return $maxTextWidth;
    }

    function generateImage($text, $style, $targetFileDir, $targetFileName)
    {
        // 1) split text into multiple lines if needed and process text (including line wrap)
        // 2) calculate dimensions of all line boxes and the text box
        // 3) calculate image box dimensions (text box + padding)
        // 4) calculate actual image dimensions
        // 5) calculate image box position
        // 6) create image and allocate colors
        // 7) fill background
        // 8) position line and shadow boxes
        // 9) draw text
        // 10) apply transforms

	    // 1) split lines
	    if (
	       (!is_null($style['string-split']))
	       &&
	       (strlen($style['string-split']) > 0)
	    )
	    {
            if ($style['string-split'] == '\n')
            {
                $splitString = "\n";
            }
            else
            {
                $splitString = $style['string-split'];
            }
            $text = str_replace( $splitString, "\n", $text);
	    }
        $lines = explode("\n", $text);

        if ($style['replace-from'])
        {
            foreach ($lines as $key => $line)
            {
                $lines[$key] = preg_replace ($style['replace-from'], $style['replace-to'], $line);
            }
        }

        if ($style['text-transform'])
        {
            foreach ($lines as $key => $line)
            {
                $lines[$key] = $this->transformText($line, $style['text-transform']);
            }
        }

        if ($style['add-spaces'])
        {
            foreach ($lines as $key => $line)
            {
                $lines[$key] = $this->addSpaces($line, $style['add-spaces']);
            }
        }

        // trim lines
        foreach ($lines as $key => $line)
        {
            $lines[$key] = trim($line);
        }

        // wrap lines if max-width is set
        if (!empty($style['max-width']))
        {
            $lines = $this->wrapLines($lines, $style);
        }
        //debug ($style);
        $lineCount = count($lines);


	    // 2) calculate dimensions of line boxes and textbox
	    $lineBoxes = $lineBoxWidths = array();
        
	    $textBox = array
	    (
	       'height'    => ($style['line-height'] * $lineCount) + ($style['extra-line-space'] * ($lineCount-1)),
	       'width'     => 0
	    );

        $maxTextWidth = $this->getMaxTextWidth( $style );
	    $lineNo = 0;

	    foreach ($lines as $key => $line)
	    {
	        $lines[$key] = trim($line);
	        $line = $lines[$key];

	        $lineBox = $this->getLineBox( $style['font-size-pt'], $style['angle'], $style['font-file'], $line, $style['line-height']);
	        $lineBoxes[$key] = $lineBox;
	        $lineBoxWidths[$key] = $lineBox['width'];
	    }


	    // get the width of the longest line
	    $maxLineBoxWidth = max($lineBoxWidths);
        if (
            (is_null($maxTextWidth)) // if max width not set
            ||
            ($lineBox['width'] <= $maxTextWidth) // or line fits in max width
        )
        {
            // set text box width to fit the line

			// is minimum width defined?
            if (empty($style['min-width']))
            {
                $textBox['width'] = $maxLineBoxWidth;
            }
            else
            {
                // is minimum width wider than the longest line?
                $textBox['width'] = ($style['min-width'] > $maxLineBoxWidth)
                    ? $style['min-width']
                    : $maxLineBoxWidth;
            }
        }
        else // max width is set and the longest line is wider than max width
        {
            // set text box to max width
            $textBox['width'] = $maxTextWidth;
        }




	    // not sure why this is needed on *nix platforms
	    $textBox['width'] += 2;

	    $textBox['top']  = $style['padding-top'];
	    $textBox['left'] = $style['padding-left'];

	    // 3) calculate image box dimensions
	    $imageBox = array(
	       'height' => $style['padding-top']  +  $textBox['height'] + $style['padding-bottom'],
	       'width'  => $style['padding-left'] + $textBox['width']  + $style['padding-right']
	    );


        // $imageBox['height'] += $extraHeight;

        // 4) calculate image dimensions
        // usually == $imageBox, but may be overriden
        // by min-height or
        // by explicit $style['width'] and/or $style['height'] definitions
	    $imageSize = array();


        $minTextBoxHeight = $textBox['height'];
        if (
            (!empty($style['min-height']))
            &&
            ($style['min-height'] > $minTextBoxHeight)
        )
        {
            $minTextBoxHeight = $style['min-height'];
        }
        $extraHeight = ($minTextBoxHeight > $textBox['height']) ? $minTextBoxHeight - $textBox['height'] : 0;

	    $imageSize['width']  = (is_null($style['width']))  ? $imageBox['width']  : (int) $style['width'];
	    $imageSize['height'] = (is_null($style['height'])) ? $imageBox['height'] + $extraHeight : (int) $style['height'];



        // 5) calculate imageBox position relative to image
        $imageBox['left'] = 0;
        if ($imageSize['width'] != $imageBox['width'])
        {
            $difference = $imageSize['width'] - $imageBox['width'];
            switch ($style['text-align'])
            {
                case 'right':
                    $imageBox['left'] = $difference;
                    break;
                case 'center':
                    $imageBox['left'] = round($difference / 2);
                    break;
                case 'left': // intentional fall-through, default unknown values to left
                default:
                    // do nothing, leave as is
            }
        }
        $imageBox['top'] = 0;
        if ($imageSize['height'] != $imageBox['height'])
        {
            $difference = $imageSize['height'] - $imageBox['height'];
            switch ($style['vertical-align'])
            {
                case 'bottom':
                    $imageBox['top'] = $difference;
                    break;
                case 'middle':
                    $imageBox['top'] = round($difference / 2);
                    break;
                case 'top': // intentional fall-through, default unknown values to top
                default:
                    // do nothing, leave as is
            }
        }

   
        // 6) create image and allocate colors
        $image = imagecreatetruecolor( $imageSize['width'], $imageSize['height']);
        $fontColor = $this->allocateColor($image, $style['color']);

        $shadowColor = null;
        if (!empty($style['shadow-color']))
        {
			$shadowColor = $this->allocateColor($image, $style['shadow-color']);
        }

		$outlineColor = null;
        if (!empty($style['outline-color']))
        {
            $outlineColor = $this->allocateColor($image, $style['outline-color']);
        }

        if (!empty($style['line-color']))
        {
            $lineColor = $this->allocateColor($image, $style['line-color']);
        }
        else
        {
            $lineColor = $fontColor;
        }


        if ($style['transparency'])
        {
            if ($style['image-type'] == 'png')
            {
                imageSaveAlpha($image, true);
                imageAlphaBlending($image, true);

                $alpha = 127;
                $backgroundColor = $this->allocateColor( $image, $style['background-color'], $alpha );
            }
            elseif ($style['image-type'] == 'gif')
            {
                $backgroundColor = $this->allocateColor( $image, $style['background-color'], true );
            }
            else
            {
                // not gif, not png
                $backgroundColor = $this->allocateColor( $image, $style['background-color'] );
            }
        }
        else
        {
            $backgroundColor = $this->allocateColor($image, $style['background-color']);
        }

        // 7) add background
        imagefill($image, 0, 0, $backgroundColor);
		$this->addBackgroud($image, $style, $imageSize);


        // 8) position line and shadow boxes
        $lineOffsetTop      = $textBox['top'] + $imageBox['top'];
        $lineBoxOffsetLeft  = $textBox['left'] + $imageBox['left'];

        $shadowBoxes = $lineTextBoxes = array();
        foreach ($lineBoxes as $key => $lineBox)
        {
            switch ($style['text-align'])
            {
                case 'right':
                    $lineOffsetLeft = $textBox['width'] - $lineBox['width'];
                    break;
                case 'center':
                    $lineOffsetLeft = round( ($textBox['width'] - $lineBox['width']) / 2 );
                    break;
                case 'left':  // intentional fall-through. default unsupported cases to left
                default:
                    $lineOffsetLeft = 0;
            }
            $line = $lines[$key];

            $textX = $lineBoxOffsetLeft + $lineOffsetLeft;

            // center image vertically in line-height box
            if ($lineBox['line-height'] > $style['font-size-px'])
            {
                $lineHeightOffset = round (($lineBox['line-height'] - $style['font-size-px']) / 2);
            }
            else
            {
                $lineHeightOffset = 0;
            }

            $textY = $lineOffsetTop + $lineBox['line-height'] + $style['line-offset'] - $lineHeightOffset;

            if (!is_null($shadowColor))
            {
                $shadowX = $textX + $style['shadow-offset-x'];
                $shadowY = $textY + $style['shadow-offset-y'];

                $boxParams = array(
                    'image' => $image,
                    'size'  => $style['font-size-pt'],
                    'angle' => $style['angle'],
                    'x'     => $shadowX,
                    'y'     => $shadowY,
                    'lineOffsetTop' => $lineOffsetTop,
                    'offsetX' => $style['shadow-offset-x'],
                    'offsetY' => $style['shadow-offset-y'],
                    'color' => $shadowColor,
                    'font'  => $this->fontDir .  $style['font-file'],
                    'line'  => $line
                );
                $shadowBoxes[] = $boxParams;
            }

			if (!is_null($outlineColor))
            {
                $boxParams = array
				(
                    'image' => $image,
                    'size'  => $style['font-size-pt'],
                    'angle' => $style['angle'],
                    'x'     => $textX - 1,
                    'y'     => $textY - 1,
                    'lineOffsetTop' => $lineOffsetTop,
                    'offsetX' => -1,
                    'offsetY' => -1,
                    'color' => $outlineColor,
                    'font'  => $this->fontDir .  $style['font-file'],
                    'line'  => $line
                );
                $shadowBoxes[] = $boxParams;

				$boxParams['x'] = $textX - 1;
				$boxParams['y'] = $textY + 1;
				$boxParams['offsetX'] = -1;
				$boxParams['offsetY'] = 1;
				$shadowBoxes[] = $boxParams;

				$boxParams['x'] = $textX + 1;
				$boxParams['y'] = $textY + 1;
				$boxParams['offsetX'] = 1;
				$boxParams['offsetY'] = 1;
				$shadowBoxes[] = $boxParams;

				$boxParams['x'] = $textX + 1;
				$boxParams['y'] = $textY - 1;
				$boxParams['offsetX'] = 1;
				$boxParams['offsetY'] = -1;
				$shadowBoxes[] = $boxParams;
            }


            $boxParams = array
            (
                'image' => $image,
                'size'  => $style['font-size-pt'],
                'angle' => $style['angle'],
                'x'     => $textX,
                'y'     => $textY,
                'lineOffsetTop' => $lineOffsetTop,
                'offsetX' => 0,
                'offsetY' => 0,
                'color' => $fontColor,
                'font'  => $this->fontDir .  $style['font-file'],
                'line'  => $line
            );
            $lineTextBoxes[] = $boxParams;

            $lineOffsetTop += $lineBox['line-height'] + $style['extra-line-space'];
        }

        // draw text

        if (isset($style['linear-gradient']))
        {
        	foreach ($shadowBoxes as $box)
        	{
                $textBox = $this->getText(
	                $box['image'],
	                $box['size'],
	                $box['angle'],
	                $box['x'],
	                $box['y'],
	                $box['color'],
	                $box['font'],
	                $box['line']
	            );

        	}

        	$temp = imagecreatetruecolor($imageSize['width'], $imageSize['height']);
        	imagealphablending($temp, true);
			imagesavealpha($temp, true);
			imagefill($temp, 0, 0, imagecolorallocatealpha($temp, 0, 0, 0, 127));
            foreach ($lineTextBoxes as $box)
        	{
                $textBox = $this->getText(
	                $temp,
	                $box['size'],
	                $box['angle'],
	                $box['x'],
	                $box['y'],
	                $box['color'],
	                $box['font'],
	                $box['line']
	            );

        	}
        	$temp = self :: applyGradient($temp, $style['linear-gradient']['angle'], $style['linear-gradient']['colorStops'], $imageSize['width'], $imageSize['height']);
        	imagecopy($image, $temp, 0, 0, 0, 0, $imageSize['width'], $imageSize['height']);
        }
        else
        {
	        $boxes = array_merge($shadowBoxes, $lineTextBoxes);

	        // debug ($boxes, 0);
	        foreach ($boxes as $box)
	        {
	            $textBox = $this->getText (
	                $box['image'],
	                $box['size'],
	                $box['angle'],
	                $box['x'],
	                $box['y'],
	                $box['color'],
	                $box['font'],
	                $box['line']
	            );

	            if (
                    (!empty($style['text-decoration']))
                    &&
                    ($style['text-decoration'] == 'underline')
                )
	            {
                    for ( $i = 0; $i < $style['line-thickness']; $i++ )
                    {
                        imageline
                        (
                            $box['image'] ,
                            $textBox[0] ,
                            $box['lineOffsetTop'] + $style['line-position'] + $box['offsetY'] + $i,
                            $textBox[2] ,
                            $box['lineOffsetTop'] + $style['line-position'] + $box['offsetY'] + $i,
                            $lineColor
                        );
                    }
	            }
	            elseif (!empty($style['underline-position']))
				{
					imageline
					(
					   $box['image'],
					   $textBox[0],
					   $style['underline-position'] + $box['offsetY'],
					   $textBox[2],
					   $style['underline-position'] + $box['offsetY'],
                       $box['color']
                    );
				}

	        }
        }

        // apply transforms
        if (isset($style['transform']))
        {
        	switch ($style['transform']['function'])
        	{
        		case 'rotate':

        			break;
        	}
        }

        $imageFunction = 'image' . $style['image-type'];

        $targetFileFullName = $targetFileDir . $targetFileName;
        $allOk = $imageFunction($image, $targetFileFullName);

        // save image info to db
        if (
            ($allOk)
        )
        {
            $imageInfo = array(
                'filename' => $targetFileName,
                'width'    => $imageSize['width'],
                'height'   => $imageSize['height'],
                'type'     => $style['image-type']
            );
            // store info in memory
            $this->imageInfoCache[$targetFileName] = $imageInfo;

            // if needed, also store in db
            if ($this->config['saveImageInfo'])
            {
                $this->saveImageInfo($imageInfo);
            }
        }

        return $allOk;
    }

	public function getImageResource($style, $imageName)
	{
        $extensionsFunctions = array
        (
            'png' => 'imagecreatefrompng',
            'gif' => 'imagecreatefromgif',
            'jpg' => 'imagecreatefromjpeg',
        );
        if(isset($style[$imageName]))
        {
            $imagePath = PATH . preg_replace("(\t|\"|\'|\(|\)|url|\s)", "", $style[$imageName]);
            if(file_exists($imagePath))
            {
                $pathinfo = pathinfo(strtolower($imagePath));
                if(isset($extensionsFunctions[$pathinfo['extension']]))
                {
                    $functionName = $extensionsFunctions[$pathinfo['extension']];
                    $image['resource'] = $functionName($imagePath);
                    $image['width'] = imagesx($image['resource']);
                    $image['height'] = imagesy($image['resource']);
                    return $image;
                }
            }
        }
	}

	public function addBackgroud(&$image, &$style, $imageSize)
	{
        $backgroundImageData = $this->getImageResource($style, 'background-image');
        $leftTopImageData = $this->getImageResource($style, 'background-image-left-top');
        $leftBottomImageData = $this->getImageResource($style, 'background-image-left-bottom');
        $rightTopImageData = $this->getImageResource($style, 'background-image-right-top');
        $rightBottomImageData = $this->getImageResource($style, 'background-image-right-bottom');

        if($leftTopImageData || $leftBottomImageData || $rightTopImageData || $rightBottomImageData || $backgroundImageData)
        {
            $style['image-type'] = 'png';
        }

        // add background image
        if($backgroundImageData)
        {
            $backgroundImage = imagecreatetruecolor( $imageSize['width'], $imageSize['height']);

            imageSaveAlpha($backgroundImage, true);
            imageAlphaBlending($backgroundImage, true);
            $alpha = 127;
            $backgroundColor = $this->allocateColor( $backgroundImage, $style['background-color'], $alpha );
            imagefill($backgroundImage, 0, 0, $backgroundColor);

            $backgroundRepeat = !empty($style['background-repeat']) ? $style['background-repeat'] : 'repeat';

            // set max width and height from repeate mode
            if($backgroundRepeat == 'repeat-x')
            {
                $maxWidth = $imageSize['width'];
                $maxHeight = $backgroundImageData['height'];
            }
            else if($backgroundRepeat == 'repeat-y')
            {
                $maxWidth = $backgroundImageData['width'];
                $maxHeight = $imageSize['height'];
            }
            else if($backgroundRepeat == 'no-repeat' || $backgroundRepeat == 'none')
            {
                $maxWidth = $backgroundImageData['width'];
                $maxHeight = $backgroundImageData['height'];
            }
            else
            {
                $maxWidth = $imageSize['width'];
                $maxHeight = $imageSize['height'];
            }

            // calculate offset
            $backgroundPositionRaw = !empty($style['background-position']) ? $style['background-position'] : 'left top';
            $backgroundPositionRaw = str_replace('px', '', $backgroundPositionRaw);
            $tmp = explode(' ', trim($backgroundPositionRaw));
            if(!isset($tmp[1]))
            {
                $tmp[1] = 'top';
            }

            // calculate x offset
            if(isPositiveInt($tmp[0]))
            {
                $backgroundOffsetX = $tmp[0];
            }
            elseif($tmp[0] == 'left')
            {
                if(isset($images['background-image-left-top']['width']))
                {
                    $backgroundOffsetX = $images['background-image-left-top']['width'];
                }
                else
                {
                    $backgroundOffsetX = 0;
                }
            }
            elseif($tmp[0] == 'center')
            {
                $backgroundOffsetX = round($imageSize['width'] / 2) - round($maxWidth / 2);
                if(isset($images['background-image-right-top']['width']) && !isset($images['background-image-left-top']['width']))
                {
                    $backgroundOffsetX -= floor($images['background-image-right-top']['width'] / 2);
                }
            }
            elseif($tmp[0] == 'right')
            {
                $backgroundOffsetX = $imageSize['width'] - $maxWidth;
                if(isset($images['background-image-right-top']['width']))
                {
                    $backgroundOffsetX -= $images['background-image-right-top']['width'];
                }
            }
            else
            {
                $backgroundOffsetX = 0;
            }

            // calculate y offset
            if(isPositiveInt($tmp[1]))
            {
                $backgroundOffsetY = $tmp[1];
            }
            elseif($tmp[1] == 'top')
            {
                $backgroundOffsetY = 0;
            }
            elseif($tmp[1] == 'center')
            {
                $backgroundOffsetY = round($imageSize['height'] / 2) - round($maxHeight / 2);
            }
            elseif($tmp[1] == 'bottom')
            {
                $backgroundOffsetY = $imageSize['height'] - $maxHeight;
            }
            else
            {
                $backgroundOffsetY = 0;
            }

            $currentWidth = 0;
            $currentHeight = 0;

            // fill with background images
            while($currentWidth < $maxWidth)
            {
                while($currentHeight < $maxHeight)
                {
                    if(($currentWidth + $backgroundImageData['width']) > $maxWidth)
                    {
                        $stepWidth = $maxWidth - $currentWidth;
                    }
                    else
                    {
                        $stepWidth = $backgroundImageData['width'];
                    }

                    if(($currentHeight + $backgroundImageData['height']) > $maxHeight)
                    {
                        $stepHeight = $maxHeight - $currentHeight;
                    }
                    else
                    {
                        $stepHeight = $backgroundImageData['height'];
                    }
                    $backgroundX = $currentWidth + $backgroundOffsetX;
                    $backgroundY = $currentHeight + $backgroundOffsetY;
                    imagecopy($backgroundImage, $backgroundImageData['resource'], $backgroundX, $backgroundY, 0, 0, $stepWidth, $stepHeight);
                    $currentHeight += $stepHeight;
                }

                $currentWidth += $stepWidth;

                if($backgroundRepeat == 'repeat-x' || $backgroundRepeat == 'repeat')
                {
                    $currentHeight = 0;
                }
            }
            imagecopy($image, $backgroundImage, 0, 0, 0, 0, $imageSize['width'], $imageSize['height']);
		}

        if($leftTopImageData || $leftBottomImageData || $rightTopImageData || $rightBottomImageData)
        {
            imagealphablending($image, false);
            imagesavealpha($image, true);

            if($leftTopImageData)
            {
                $style['image-type'] = 'png';
                $this->addTransparentImage($image, $leftTopImageData);
            }

            if($rightTopImageData)
            {
                $style['image-type'] = 'png';
                $cornerImageX = $imageSize['width'] - $rightTopImageData['width'];
                $this->addTransparentImage($image, $rightTopImageData, $cornerImageX);
            }

            if($leftBottomImageData)
            {
                $style['image-type'] = 'png';
                $cornerImageY = $imageSize['height'] - $leftBottomImageData['height'];
                $this->addTransparentImage($image, $leftBottomImageData, 0, $cornerImageY);
            }

            if($rightBottomImageData)
            {
                $style['image-type'] = 'png';
                $cornerImageX = $imageSize['width'] - $rightBottomImageData['width'];
                $cornerImageY = $imageSize['height'] - $rightBottomImageData['height'];
                $this->addTransparentImage($image, $rightBottomImageData, $cornerImageX, $cornerImageY);
            }
            imagealphablending($image, true);
        }
	}

    public function addTransparentImage(&$destination, $imageSource, $xPos = 0, $yPos = 0)
    {
        $w = $imageSource['width'];
        $h = $imageSource['height'];
        for( $y = 0; $y < $h; $y++ ) // y cikls
        {
            for($x = 0; $x < $w; $x++) // x cikls
            {
                $x2 = $x + $xPos;
                $y2 = $y + $yPos;
                $color = imagecolorallocatealpha( $destination, 255, 255, 255, 127);
                imagesetpixel($destination, $x2, $y2, $color);
            }
        }
        imagecopy($destination, $imageSource['resource'], $xPos, $yPos, 0, 0, $imageSource['width'], $imageSource['height']);
    //    debug('asd');
    }

    function getImageCacheUrl()
    {
        return $this->cacheWww;
    }

    function allocateColor($image, $color = '#FFFFFF', $alphaOrTransparent = null)
    {
		if (
            (is_string($color))
            &&
            (strpos( $color, 'rgb' ) !== false )
        )
		{
			$rawColor = preg_replace( '/rgb|a|\(|\)| /i', '', $color );
			$rawColor = explode( ',', $rawColor );
			if (count( $rawColor ) < 3)
			{
				// error
				$this->error('Invalid color: "' . $color . '".');
			}
            $rgb = array
            (
    			'red'   => $rawColor[0],
    			'green' => $rawColor[1],
                'blue'  => $rawColor[2]
            );

			if (
                ( isset( $rawColor[3] ) )
                &&
                (is_null($alphaOrTransparent))
            )
			{
			    // function argument alpha overrides alpha in color definition
				$alphaOrTransparent = max( 0, min( 127, round( ( 1 - $rawColor[3] ) * 127 ) ) );
			}

		}
        elseif ( is_string( $color ) )
		{
			$rgb = $this->hex2rgb($color);
		}
		else
		{
			$rgb = $color;
		}

        if (is_null($alphaOrTransparent))
        {
            $result = imagecolorallocate($image, $rgb['red'], $rgb['green'], $rgb['blue']);
        }
        elseif ($alphaOrTransparent === true)
        {
            $result = imagecolorallocate($image, $rgb['red'], $rgb['green'], $rgb['blue']);
            imagecolortransparent($image, $result);
        }
        else
        {
            $result = imagecolorallocatealpha($image, $rgb['red'], $rgb['green'], $rgb['blue'], $alphaOrTransparent);
        }
        return $result;
    }

    function hex2rgb($hex)
    {
        // remove '#'
        if(substr($hex,0,1) == '#')
        {
            $hex = substr($hex,1) ;
        }

        if (!preg_match('/^([a-f0-9]{3}){1,2}+$/i', $hex))
        {
             $this->error('Invalid color: "' . $hex . '"');
        }

        // expand short form ('fff') color
        if (strlen($hex) == 3)
        {
            $char1 = substr($hex,0,1);
            $char2 = substr($hex,1,1);
            $char3 = substr($hex,2,1);

            $hex = $char1 . $char1 . $char2 . $char2 . $char3 . $char3;
        }


        // convert
        $rgb['red']     = hexdec(substr($hex,0,2));
        $rgb['green']   = hexdec(substr($hex,2,2));
        $rgb['blue']    = hexdec(substr($hex,4,2));

        return $rgb ;
    }

    function parseShorthandValue($value)
    {
        $value = explode(' ', trim($value));

        foreach ($value as $key => $val)
        {
            $value[$key] = (int) $val;
        }

        $count = count($value);
        if ($count == 1)
        {
            $value[1] = $value[2] = $value[3] = $value[0];
        }
        elseif ($count == 2)
        {
            $value[2] = $value[0];
            $value[3] = $value[1];
        }
        elseif ($count == 4)
        {

        }
        else
        {
            return false;
        }
        return $value;
    }

    function detectFreeTypeVersion()
    {
        if (function_exists('imagefttext'))
        {
            $this->freeTypeVersion = 2;
        }
        elseif (function_exists('imagettftext'))
        {
            $this->freeTypeVersion = 1;
        }
        else
        {
            $this->error('FreeType not found.');
        }
    }

    function getBBox($fontSize, $angle, $fontFile, $line, $extraInfo = array())
    {
        $box = null;

        if ($this->freeTypeVersion == 1)
        {
            $box = imagettfbbox($fontSize, $angle, $fontFile, $line);
        }
        elseif ($this->freeTypeVersion == 2)
        {
            $box = imageftbbox($fontSize, $angle, $fontFile, $line, $extraInfo);
        }

        if (is_null($box))
        {
            $this->error('Unsupported FreeType version.');
        }

        // in some cases imageftbbox returns -2147483648 ( 2^31 * -1 ) for lower and upper right corner positions.
        // strangely, simply repeating the call usually seems to help.
        if (
            ($box[2] == -2147483648)
            &&
            ($box[4] == -2147483648)
            &&
            ($this->infiniteLoopProtection > 0)
        )
        {
            $this->infiniteLoopProtection--;
            $box = $this->getBBox( $fontSize, $angle, $fontFile, $line, $extraInfo);
        }

        return $box;
    }

    function getText($image, $fontSize, $angle, $textX, $textY, $fontColor, $fontFile, $line, $extraInfo = array())
    {
        if ($this->freeTypeVersion == 1)
        {
            return imagettftext (
                $image,
                $fontSize,
                $angle,
                $textX,
                $textY,
                $fontColor,
                $fontFile,
                $line
            );

        }
        elseif ($this->freeTypeVersion == 2)
        {

            return imagefttext (
                $image,
                $fontSize,
                $angle,
                $textX,
                $textY,
                $fontColor,
                $fontFile,
                $line,
                $extraInfo
            );

        }
        $this->error('Unsupported FreeType version.');

    }

    function saveImageInfo($imageInfo)
    {
        $imageInfo['created'] = 'NOW()';
        $p = new processing();
        $p->db_replace_entry('image_text_info', $imageInfo, false, false, true, true, array('created'));
        return;
    }

    function getImageInfo( $fileName )
    {
        // returns info array for a previously generated image

        if (isset($this->imageInfoCache[$fileName]))
        {
            return $this->imageInfoCache[$fileName];
        }

        $sql = '
            SELECT
                filename, width, height, type
            FROM
                `image_text_info`
            WHERE `filename` = "' . dbse($fileName) . '"
        ';

        $data = dbGetRow($sql);
        return $data;
    }

    function transformText($text, $transformMode)
    {
        if ($transformMode == 'uppercase')
        {
            $text = mb_strtoupper($text);
        }
        elseif ($transformMode == 'lowercase')
        {
            $text = mb_strtolower($text);
        }
        return $text;
    }

    function addSpaces($text, $spaces)
    {
        $search = "(.)";
        $replace = "\\0" . str_repeat(' ', $spaces);
        $text = mb_ereg_replace($search, $replace, $text);
        $text = trim ($text);
        return $text;
    }

    public static function getStylesheetLastModTime()
    {
        $imageText = singleton::get(__CLASS__);
        $mtime = date('Y-m-d H:i:s', filemtime( $imageText->styleFile ));
        return $mtime;
    }

    public static function applyGradient($image, $angle, $colorStops, $originalWidth, $originalHeight)
    {
		$outputImage = imagecreatetruecolor((int) $originalWidth, (int) $originalHeight);
		imagealphablending($outputImage, true);
		imagesavealpha($outputImage, true);
		imagefill($outputImage, 0, 0, imagecolorallocatealpha($outputImage, 0, 0, 0, 127));

		// Generate gradient image
		$trigonometryAngle = deg2rad($angle % 180);
		$sin = sin($trigonometryAngle);
		$cos = cos($trigonometryAngle);
		$width = round($sin * $originalWidth + $cos * $originalHeight);
		$height = round($cos * $originalWidth + $sin * $originalHeight);
		$im = imagecreatetruecolor(
			$width,
			$height
		);
		do
		{
			$start = array_shift($colorStops);
			$end = reset($colorStops);
			if (!$end) break;
			$fromY = floor($height * ($start['position'] / 100));
			$toY = floor($height * ($end['position'] / 100));
			$steps = $toY - $fromY;

			$fromChannels = sscanf($start['color'], '%2x%2x%2x');
			$toChannels = sscanf($end['color'], '%2x%2x%2x');
			$stepChannels = array ();
			foreach ($fromChannels as $i => $channelValue)
			{
				$stepChannels[$i] = ($channelValue - $toChannels[$i]) / $steps;
			}

			for ($i = 0; $i < $steps; $i++)
			{
				$r = floor($fromChannels[0] - ($stepChannels[0] * $i));
				$g = floor($fromChannels[1] - ($stepChannels[1] * $i));
				$b = floor($fromChannels[2] - ($stepChannels[2] * $i));
				imageline($im, 0, $fromY + $i, $width, $fromY + $i, imagecolorallocate($im, $r, $g, $b));
			}
		}
		while (!empty($colorStops));

		// Rotated gradient created
		$rotate = imagerotate($im, 90 - $angle, imagecolorallocate($im, 0, 0, 0));
		imagedestroy($im);

		// Create clipped gradient
		$rWidth = imagesx($rotate);
		$rHeight = imagesy($rotate);

		$gradient = imagecreatetruecolor($originalWidth, $originalHeight);
		imagecopy($gradient, $rotate, 0, 0, ($rWidth - $originalWidth) / 2, ($rHeight - $originalHeight) / 2, $originalWidth, $originalHeight);
		imagedestroy($rotate);

		for ($x = 0; $x < imagesx($image); $x++)
		{
			for ($y = 0; $y < imagesy($image); $y++)
			{
				$color = imagecolorat($image, $x, $y);
				$a = ($color >> 24) & 0xff;
				$color = imagecolorat($gradient, $x, $y);
				$r = ($color >> 16) & 0xff;
			    $g = ($color >> 8) & 0xff;
			    $b = $color & 0xff;
				imagesetpixel($outputImage, $x, $y, imagecolorallocatealpha($outputImage, $r, $g, $b, $a));
			}
		}

		imagedestroy($image);
    	return $outputImage;
    }

    function getDefaultLineThickness( $fontSize )
    {
        // increase line thickness by 1 px with each 14 px in font size,
        // but only from 20 up
        $step       = 14;
        $stepOffset = 6;

        $size = $fontSize - $stepOffset;
        if ($size < 1)
        {
            $size = 1;
        }
        $lineThickness = ceil( $size / $step ) ;
        return $lineThickness;
    }

    function getDefaultLinePosition( $fontSize )
    {
        // by default position the underline slightly above the bottom of each text box

        $linePosition = $fontSize - floor( $fontSize * 0.09 ) + floor( $fontSize / 24 );
        return $linePosition;
    }

    function getRawStyle($name)
    {
        if (!isset($this->rawStyles[$name]))
        {
            return null;
        }

        $style = $this->rawStyles[$name];

        if (
            (isset($style['extends'])) // extends is set
            &&
            ($style['extends'] != $name) // and parent style is not self
        )
        {
            if ( in_array($name, $this->inheritanceStack ))
            {
                $this->error('Circular inheritance detected in text class "' . $name . '".' );
            }

            array_push( $this->inheritanceStack, $name );

            $parentStyle = $this->getRawStyle( $style['extends'] );
            if (!$parentStyle)
            {
                $this->error('Text class not defined: "' . $style['extends'] . '".');
            }

            array_pop( $this->inheritanceStack );

            unset( $parentStyle['extends'] );

            $style = array_merge($parentStyle, $style);
        }

        return $style;

    }

    function error($message)
    {
        $message = __CLASS__ . ': ' . $message;
        trigger_error($message, E_USER_ERROR);
        throw new Exception( $message );
    }
}
