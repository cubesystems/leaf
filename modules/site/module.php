<?

class site extends leaf_module
{
    // Don't show these templates in menu
    protected $skipTemplates = array();

    // Auto filled from shared/config
    protected $authorizedTemplates = array();
	
	// Template which is responsible for authorization (ex. users/authorization)
    protected $authorizationTemplate = null;

	
	protected $root = null;
    protected $openedObject = null;
	protected $menu = null;

    protected $pathPart = null;

    protected $user = null;
	
	public function __construct()
	{
        parent::__construct();
		
		$this->root         = $this->getLanguageRoot();
        $this->openedObject = _core_load_object( leaf_get( 'object_id' ) );
        $this->pathPart    	= leaf_get( 'path_part' );
	}

	public function output()
    {
        // Send 404 if opened object not available
        if( !$this->openedObject )
        {
			$rewrite = _core_load_module('leaf_rewrite');
            $rewrite->error_404( $force404 = false, $outputHtml = true );
            return;
        }
        
		
        // Load Styles and scripts
        $this->loadAssets();
        
		
        // Start flash message handler
        $useSession = leaf_get_property('useSession', false);
        if(
            (!empty($useSession))
            &&
            ($useSession === true)
        )
        {
            leafFlash::gc();
        }
		
		
        // Create menu for opened object
        $this->menu = new Menu( $this->openedObject );
        $this->prepareMenuTemplates();
		
		
        // Handle opened object authorization
        $this->handleAuthorization();
		
		
        // run viewObject only if path parts are empty or object has been marked as supporting them
        if( empty( $this->pathPart ) || !empty( $this->openedObject->hasPathParts ) )
        {
            $this->openedObject->content = $this->openedObject->viewObject( array(), $this );
            $this->openedObject->content = translate_objects_id( $this->openedObject->content );
            $this->openedObject->content = embedObject::replacePlaceholders( $this->openedObject->content );
        }


        // If path_part isn't handled, force error 404
		if( leaf_get( 'path_part' ) )
		{
			$rewrite = _core_load_module('leaf_rewrite');
            $rewrite->error_404( $force404 = false, $outputHtml = true );
            return;
		}


        $assign['openedObject'] = $this->openedObject;
        $assign['site'] = $this;

        // Render output depending of output mode
        switch( $this->openedObject->getOutputMode() )
        {
            case "page":

                if( isset( $this->openedObject->customLayout ) )
                {
                    $content = alias_cache::fillInAliases( $this->openedObject->content );
                }
                else
                {
                    $assign['menu']             = $this->menu;
                    $assign['properties']       = leaf_get('properties');
                    $assign['user']             = $this->user;
                    $assign['metaDescription']  = $this->getDescription( $this->openedObject );
                    $assign['metaKeywords']     = $this->getKeywords( $this->openedObject );
                    
                    if( $this->root )
                    {
                        $assign['root']         = $this->root;
                        $assign['rootVars']     = $this->root->object_data['data'];
                    }
					
                    $content = $this->renderView('content.tpl', $assign );
                }

			break;

            case "text":

                header('Content-Type: text/plain');
                $content = alias_cache::fillInAliases( $this->openedObject->content );

			break;

            case "xml":

                header('Content-Type: text/xml');
                $content = $this->renderView( 'xml.tpl', $assign );

			break;

            default:
                throw new Exception( 'Unsupported output mode' );
        }

		return $content;
    }


    public function renderView( $template, $assign = array() )
    {
		$smarty = new leaf_smarty( $this->module_path .  'templates/' );
        $smarty->register_outputfilter( array( 'alias_cache', 'fillInAliases' ) );
        $smarty->assign( $assign );
		
        return $smarty->fetch( $template );
    }


	public static function getLanguageRoot()
	{
	    return _core_load_object( leaf_get( 'root' ) );
    }


	public function getMenuSkipTemplates()
	{
        return $this->menu->getSkipTemplates();
	}


    public function getDescription( $object )
	{
		$commonFieldValue = objectTree::getFirstCommonFieldValue( $object, 'metaDescription' );
		
		if( empty( $commonFieldValue['value'] ) )
		{
			return null;
		}
		
		return $commonFieldValue['value'];
	}

	
    public function getKeywords( $object )
	{
		$commonFieldValue = objectTree::getFirstCommonFieldValue( $object, 'metaKeywords' );
		
		if( empty( $commonFieldValue['value'] ) )
		{
			return null;
		}
		
		return $commonFieldValue['value'];
	}


    protected function loadAssets()
    {
		_core_add_css( WWW . 'styles/base.css' );
		_core_add_css( WWW . 'styles/textFormat.css' );
		_core_add_css( WWW . 'styles/style.css' );
		_core_add_css( WWW . 'styles/ie7.css', 'lt IE 8' );
		_core_add_css( WWW . 'styles/ie_all.css', 'IE' );
		
        _core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );

		if(
            ( !empty( $_SERVER['HTTP_USER_AGENT'] ) )
            &&
            ( strpos( $_SERVER['HTTP_USER_AGENT'], 'iPhone' ) !== false )
        )
        {
			_core_add_js( SHARED_WWW . 'js/enhanceInputTypes.js' );
        }
    }


    protected function prepareMenuTemplates()
    {
		if( !$this->user )
		{
			$this->skipTemplates = array_unique( array_merge( $this->skipTemplates, $this->authorizedTemplates ) );
		}
        
        // Set templates to menu
        $this->menu->setSkipTemplates( $this->skipTemplates );
    }


    protected function openedObjectRequiresAuthorization()
    {
        if( !$this->openedObject || $this->user )
        {
            return false;
        }

        if( method_exists( $this->openedObject, 'requiresAuthorization' ) )
        {
            return $this->openedObject->requiresAuthorization();
        }

        foreach( $this->menu->getObjectsTree() as $item )
        {
            if( empty( $item['template'] ) )
            {
                continue;
            }

            if( in_array( $item['template'], $this->authorizedTemplates ) )
            {
                return true;
            }
        }
    }
	
	
    protected function handleAuthorization()
    {
        if( !$this->openedObjectRequiresAuthorization() )
        {
            return false;
        }
		
        $authorizationObject = objectTree::getFirstObject( $this->authorizationTemplate );
		
        // Authorization is required, but template is not available. Send 403 - Access Denied
        if( !$authorizationObject )
        {
            leafHttp::send403();
        }
		
        // Override opened object and clear path_part
        $this->openedObject = $authorizationObject;
        leaf_set( 'path_part', null );
    }

}
