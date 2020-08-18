<?php
class input
{
	private $dir;
	private $templateDir = 'templates';

	protected $type;
	protected $params;

	function __construct( $params )
	{
		$this->dir    = dirname(realpath(__FILE__)) . '/';
		$this->type   = $params['type'];
		$this->params = $params;
		
		// load common resources
		_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );

		if (!isset($this->params['aliasContext']))
		{
            $this->params['aliasContext'] = 'input';
		}

		// load type specific resources
		self::load( $this->type );
		// do type specific processing
		switch( $this->type )
		{
            case 'date':
                if( empty( $params['value'] ) && !empty( $params['default'] ) )
                {
                    $this->params['value'] = date('Y-m-d', strtotime($params['default']));
                }
			break;

			case 'objectlink':
			break;
			
			case 'select':
				// set default creationDialog value
				if( !isset( $this->params['creationDialog'] ) )
				{
					$this->params['creationDialog'] = true;
                }

				$moduleName = $params[ 'module' ];
				$this->params['moduleName'] = $moduleName;
				$autoload = false;
				if( isset( $params['autoload'] ) )
				{
					$autoload = $params['autoload'];
				}
				if( !$autoload )
				{
					require_once( PATH . 'modules/' . $moduleName . '/' . 'module.php' );
				}
				
				$module = new $moduleName;
				$this->params['module'] = $module;
				// if selection model is "search", don't load all objects upfront
				if( !empty( $params['selectionModel'] ) && $params['selectionModel'] == 'search' )
				{
					//$this->params['collection'] = call_user_func( array( $module->getMainObjectClass(), 'getCollection' ), array(), $module->getItemsPerPage(), 1 );
					// assign extra response fields
					if( isset( $module->extraResponseFields ) && is_array( $module->extraResponseFields ) )
					{
						$this->params[ 'extraResponseFields' ] = implode( ',', array_keys( $module->extraResponseFields ) );
					}
				}
				else
				{
					$this->params['collection'] = call_user_func( array( $module->getMainObjectClass(), 'getCollection' ) );
				}
				// setup value
				if( empty( $params['value'] ) && !empty( $params['selectedObject'] ) )
				{
					$this->params['value'] = $params['selectedObject']->id;
				}
				if
				(
					empty( $params['valueText'] )
					&&
					!empty( $params['selectedObject'] )
					&&
					is_object( $params['selectedObject'] )
					&&
					method_exists( $params['selectedObject'], 'getDisplayString' )
				)
				{
					$this->params['valueText'] = $params['selectedObject']->getDisplayString();
				}
			break;
			
			case 'leafFile':
    			$this->params['inputFieldSuffix'] = leafFile::inputFieldSuffix;
    			if (!empty($this->params['file']) && ($this->params['file'] != '-1'))
    			{
    			    $this->params['fileInstance'] = getObject('leafFile', $this->params['file']);
    			}
    		break;
	
			case 'geoPoint':
				// load config
				$config = leaf_get( 'properties', 'googleMaps' );
				if( is_null( $config ) )
				{
				    $this->params['error'] = 'googleMapsConfigMissing';
				    return;
				}
				if( is_array( $config ) )
				{
    				// load key
    				if
					(
    				    ( !isset($this->params['googleMapsKey'] ) )
    				    &&
    				    ( isset($config['key'] ) )
                    )
                    {
                        $this->params['googleMapsKey'] = $config['key'];
    				}
                    
                    if (!isset($config['version']))
                    {
                        $config['version'] = 'flash';
                    }
                    

                    // id and name attributes must be set
                    if
                    (
                        ($config['version'] == 'flash')
                        &&
                        (
                            ( empty( $this->params['id'] ) )
                            ||
                            ( empty( $this->params['name'] ) )
                        )
                    )
                    {
                        $this->params['error'] = 'geoPointMissingIdOrName';
                        return;
                    }
                
                

                    if (!in_array($config['version'], array('flash', 'v3')))
                    {
                        trigger_error('Unrecognized geoPoint version.', E_USER_WARNING );
                        $config['version'] = 'flash';
                    }                    
                    
    				// load config defaults where needed
    				$configVars = array('version', 'defaultZoom', 'centerLng', 'centerLat');
    				foreach ($configVars as $var)
    				{
    				    if( isset( $this->params[$var] ) )
    				    {
    				        continue; // param explicitly set in attribute
    				    }
    				    if( isset( $config[$var] ) )
    				    {
    				        $this->params[$var] = $config[$var];
    				    }
    				}
				}
				if (empty($this->params['googleMapsKey']))
				{
				    $this->params['error'] = 'geoPointMissingGoogleMapsKey';
				}
                
                if (!isset($this->params['useSearch']))
                {
                    $this->params['useSearch'] = true;
                }   
                
				// load value
				$lat = $lng = null;
				if( isset( $this->params['value'] ) )
				{
				    $value = $this->params['value'];
				    // value given as array
				    if
					(
				        ( is_array( $value ) )
				        &&
				        ( array_key_exists( 'lat', $value ) )
				        &&
				        ( array_key_exists( 'lng', $value ) )
                    )
				    {
				        $lat = $value['lat'];
				        $lng = $value['lng'];
				    }
				    elseif( is_string( $value ) )
				    {
				        $value = explode( ';', $value );
				        if( count( $value ) == 2 )
				        {
				            $lat = trim( $value[0] );
				            $lng = trim( $value[1] );
				        }
				    }
				}
			    // values given as separate values
			    if( isset( $this->params['valueLat'] ) )
			    {
			        $lat = $this->params['valueLat'];
			    }
			    if(isset( $this->params['valueLng'] ) )
			    {
			        $lng = $this->params['valueLng'];
			    }
			    $this->params['lat'] = $lat;
			    $this->params['lng'] = $lng;
			break;
		}
	}

	public static function load( $type )
	{
		switch( $type )
		{
			case '_dialog': // internal resource-package for jquery dialog
				// jquery core
				_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
				// ui dependencies
                _core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
                _core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.widget.min.js');
                _core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.mouse.min.js');
                _core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.position.min.js');

                _core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.draggable.min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.resizable.min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.dialog.min.js' );
				
				// theme
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.resizable.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.dialog.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css' );
				
				
			break;
			case 'date':
				// jquery core
				_core_add_js(  SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
				// ui dependencies
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/effects.core.min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/effects.slide.min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.datepicker.min.js' );
				// type-specific code
				_core_add_js( SHARED_WWW . 'classes/input/js/date.js' );
				// theme
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.datepicker.css' );
				// type-specific style
				_core_add_css( SHARED_WWW . 'classes/input/css/date.css' );

				// localization - if the required files do not exist, no localization will occur
				$globalLanguageName = leaf_get( 'properties', 'language_name' );
				// load custom language
				if( !empty( $params['language'] ) )
				{
                    $filename = 'ui.datepicker-' . stringtolatin($params['language'], true, true) . '.js';
				}
				// load global language
				else if( !empty( $globalLanguageName ) )
				{
                    $filename = 'ui.datepicker-' . $globalLanguageName . '.js';
				}
                
                $paths = array
                (
                    'classes/input/js/', 
                    '3rdpart/jquery/ui/i18n/'
                );
                
                foreach ($paths as $path)
                {
                    $filePath = SHARED_PATH . $path . $filename; 
                    $fileUrl  = SHARED_WWW . $path . $filename;
                    
                    
                    if (
                        (file_exists( $filePath ) )
                        &&
                        (is_file( $filePath ))
                    )
                    {
                        _core_add_js( $fileUrl );
                        break;
                    }
                }
                
			break;
			case 'objectlink':
				self::load( '_dialog' );
				// custom code dependecies
				_core_add_js( SHARED_WWW . 'js/Leaf.js' );
				_core_add_js( SHARED_WWW . 'js/Validation.class.js' );
				// type-specific code
				_core_add_js( SHARED_WWW . 'classes/input/js/objectlink.js' );
				// leafDialog
				_core_add_css( SHARED_WWW . 'styles/leafDialog.css' );
				// type-specific style
				_core_add_css( SHARED_WWW . 'classes/input/css/objectlink.css' );
			break;
			case 'select':
				self::load( '_dialog' );
				// type-specific code
				_core_add_js( SHARED_WWW . '3rdpart/js.sha1/sha1.min.js' );
				_core_add_js( SHARED_WWW . 'classes/input/js/select.js' );
				// yui autocomplete
				_core_add_css( SHARED_WWW . '3rdpart/yui/autocomplete/assets/skins/sam/autocomplete.css' );
				_core_add_js( SHARED_WWW . '3rdpart/yui/yahoo-dom-event/yahoo-dom-event.js' );
				_core_add_js( SHARED_WWW . '3rdpart/yui/datasource/datasource-min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/yui/connection/connection-min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/yui/autocomplete/autocomplete-min.js' );
				// type-specific style
				_core_add_css( SHARED_WWW . 'classes/input/css/select.css' );
				_core_add_css( SHARED_WWW . 'styles/leafDialog.css' );
			break;
			case 'leafFile':
				_core_add_js(  SHARED_WWW . 'classes/input/js/leafFile.js' );
				_core_add_css( SHARED_WWW . 'classes/input/css/leafFile.css' );
    		break;
			case 'geoPoint':
                _core_add_js(  SHARED_WWW . '3rdpart/swfobject/swfobject.js');
				_core_add_js(  SHARED_WWW . 'classes/input/js/geoPoint.js' );
				_core_add_css( SHARED_WWW . 'classes/input/css/geoPoint.css' );
			break;
			case 'filepicker':
				self::load( 'objectlink' );
				// tabs
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.tabs.min.js');
				_core_add_css(SHARED_WWW . '3rdpart/jquery/themes/base/ui.tabs.css');
				// 
				_core_add_js(  SHARED_WWW . 'js/RequestUrl.class.js' );
				_core_add_js(  SHARED_WWW . 'classes/input/js/richtextImageDialog.class.js' );
				// type-specific code
				_core_add_js( SHARED_WWW . 'classes/input/js/filepicker.js' );
				// css
				_core_add_css( SHARED_WWW . 'classes/input/css/richtextImageDialog.css' );
				_core_add_css( SHARED_WWW . 'styles/leafDialog.css' );
			break;
		}
	}

	function output()
	{
		$template = new leaf_smarty( $this->dir . $this->templateDir );
		$template->assign( $this->params );

		if( isset( $this->params['buttonImage'] ) && empty( $this->params['buttonImage'] ) )
		{
			$template->assign( 'removeButtonImage', true );
		}

        return $template->fetch( $this->type . '.tpl' );
	}
}
?>
