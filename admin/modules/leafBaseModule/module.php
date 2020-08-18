<?php
class leafBaseModule extends leaf_module
{
	public $actions = array( 'save', 'delete' );
	public $output_actions = array( 'all', 'view',  'edit', 'confirmDelete', 'saveAndRespond', 'deleteAndRespond' );
	public $default_output_function = 'all';

	protected $mainObjectClass = NULL;
	protected $itemsPerPage = 40;
	protected $maxPagesInNavigation = 10;

	protected $panelLayout = true;
    protected $strip = true;
    
	public $features = array
	(
		'create' => true,
		'view' 	 => true,
		'edit' 	 => true,
		'delete' => true
	);
	
	public $featureOverrides = array();
	
	protected $saveMode = false;
	
	public $tableMode = 'html'; // possible values: "html", "css"
	
	public $continuousScroll = false;
	
	public $submenuGroupName;
	public $menuSections = array();
	public $activeMenuSection;
	public $activeMenuItem;

	protected $response = array();
    
    protected $defaultOrderColumn = null;
    protected $defaultOrderDirection = null;

    public $message = null;

	public function __construct()
	{
		parent::__construct();
		
		// base module is built for extending only
		if( get_class( $this ) == __CLASS__ )
		{
			foreach( $this->getSubMenu() as $section )
			{
				foreach( $section as $itemName => $menuItem )
				{
					if( $this->isDisabledMenuItem( $itemName ) == false )
					{
						leafHttp::redirect( $menuItem['url'] );
					}
				}
			}
            
            trigger_error('No enabled modules found in leafBaseModuleConfig menu.', E_USER_ERROR );
		}

		// apply feature overrides
		foreach( $this->featureOverrides as $name => $value )
		{
			$this->features[ $name ] = $value;
		}
        
        // highlight menu item
        $this->setActiveMenuItem( get_class( $this ) );            
	}
	
	/**
	 * return an array of parent modules, that might have own templates
	 */
	public function getParentClasses()
	{
		$parents = array();
		$parents[] = $className = get_class( $this );
		while( get_parent_class( $className ) != 'leaf_module' )
		{
			$parents[] = $className = get_parent_class( $className );
		}
		return $parents;
	}
	
	/**
	 * returns tpl file name for includes
	 */
	public function pathTo( $name )
	{
	    $parents = $this->getParentClasses();
        
		foreach( $parents as $className )
		{
			$tpl = realpath( $this->module_path . '../' . $className . '/templates' . '/' . $name . '.tpl' );

			if( file_exists( $tpl ) )
			{
				return $tpl;
			}
		}
        
		// no file found - return something to get registered in error log
		$tpl = realpath( $this->module_path . '../' . get_class( $this ) . '/templates')  . '/' . $name . '.tpl';
		return $tpl;
	}

	public function output()
	{
		// if this is an xml request call
		if( !empty($_GET['ajax']) && $_GET['ajax'] == true )
		{
			$html = parent::output();
			// strip
            if ($this->strip)
            {
                $patterns = array( "/\r\n/i", "/\r/i", "/\n/i", "/\t/i", '/  /i' );
                $html = preg_replace( $patterns, ' ', $html );
            }
			if( !empty( $_GET['json'] ) )
			{
				if( isset( $_GET['html'] ) == false || $_GET['html'] == 1 )
				{
					$this->response['html'] = $html;
				}
				die( json_encode( $this->response ) );
			}
			else
			{
				die( $html );
			}
		}
		// normal output
		//_core_add_css( SHARED_WWW . 'styles/button.css' );
		_core_add_css( WWW . 'styles/leafTable.css');
		
		
		_core_add_css( WWW . 'modules/' . __CLASS__ . '/module.css');
		if( file_exists( $this->module_path . 'module.css' ) )
		{
			_core_add_css( $this->module_www . 'module.css');
		}

		_core_add_js( SHARED_WWW . 'classes/processing/validation_assigner.js' );
		_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
		_core_add_js( SHARED_WWW . '3rdpart/jquery/plugins/cookie/jquery.cookie.min.js' );
		_core_add_js( SHARED_WWW . '3rdpart/firebug/firebugx.js' );
		_core_add_js( SHARED_WWW . 'js/RequestUrl.class.js' );
		_core_add_js( WWW . 'modules/' . __CLASS__ . '/module.js');
		
		$parents = array_reverse( $this->getParentClasses() );
		foreach( $parents as $className )
		{
			if( file_exists( PATH . 'modules/' . $className . '/module.css' ) )
			{
				_core_add_css( WWW . 'modules/' . $className . '/module.css' );
			}
			if( file_exists( PATH . 'modules/' . $className . '/module.js' ) )
			{
				_core_add_js( WWW . 'modules/' . $className . '/module.js' );
			}
		}
		
		if( file_exists( $this->module_path . 'module.js' ) )
		{
			_core_add_js( $this->module_www . 'module.js' );
		}
		// browser quirks
		//_core_add_js( WWW . 'js/BrowserDetect.js' );
		//_core_add_js( WWW . 'js/browserQuirks.js' );
		
		$content = parent::output();
        
		if( $this->panelLayout )
		{
			_core_add_css( WWW . 'styles/panelLayout.css' );
			$content = $this->moduleTemplate( '_container', array( 'content' => $content ) );
		}
		return $content;
	}

	public function all( $extraParams = array() )
	{
       
		// page
		$page = 1;
		if( !empty($_GET['page']) && is_numeric($_GET['page']) )
		{
			$page = $_GET['page'];
		}
		// collect objects
		$params = array();
		if (!empty($_GET['search']))
		{
			$params['search'] = $_GET['search'];
		}
		$params['orderBy'] = get($_GET, 'orderBy', $this->defaultOrderColumn);
		$params['direction'] = (get($_GET, 'direction')) ? $_GET['direction'] : (($this->defaultOrderDirection) ? $this->defaultOrderDirection : 'asc');
		foreach( $extraParams as $key => $value )
		{
			$params[ $key ] = $value;
		}
        
        $assign['columns'] = $this->calculateColumns( $params );
        
		$assign['collection'] = call_user_func(array($this->mainObjectClass, 'getCollection'), $params, $this->itemsPerPage, $page);
		$this->response['collection'] = array();
		foreach( $assign['collection'] as $item )
		{
			$values = array
			(
				'id' 		   => $item->id,
				'totalResults' => $assign['collection']->total,
			);
			if( method_exists( $item, 'getDisplayString' ) )
			{
				$values['displayString'] = $item->getDisplayString();
			}
			if( property_exists( $item, 'name' ) )
			{
				$values['name'] = $item->name;
			}
			if( !method_exists( $item, 'getDisplayString' ) && property_exists( $item, 'name' ) )
			{
				$values['displayString'] = $item->name;
			}
			if( !empty( $this->extraResponseFields ) )
			{
				foreach( $this->extraResponseFields as $name => $description )
				{
					if( !empty( $description[ 'method' ] ) )
					{
						$method = $description[ 'method' ];
						$values[ $name ] = $item->$method();
					}
					elseif( !empty( $description['property'] ) )
					{
						$property = $description[ 'property' ];
						$values[ $name ] = $item->$property;
					}
				}
			}
			$this->response['collection'][] = $values;
		}
		
		// construct page navigation
		$assign['pageNavigation'] = pagedNavigation::getFromList( $assign['collection'], $this->maxPagesInNavigation );
		return $assign;
	}

    public function calculateColumns( $params )
    {
        // prepare column / heading data for table in all()
        
        $columnNames = $this->listColumns();
        $columns = array();
        
        if (
            (!empty($this->defaultOrderColumn)) 
            && 
            (in_array($this->defaultOrderColumn, $columnNames))
        )
        {
            $defaultOrderColumn = $this->defaultOrderColumn;
        }
        else
        {
            $defaultOrderColumn = reset($columnNames);
        }
            
        $defaultOrderDirection = (!empty($this->defaultOrderDirection)) ? $this->defaultOrderDirection : 'asc';
        
        $orderBy = get($params, 'orderBy', $defaultOrderColumn);
        $direction = get($params, 'direction', $defaultOrderDirection);
        

        if (!empty($columnNames))
        {
            foreach ($columnNames as $columnName)
            {
                $column = array('name' => $columnName);
                
                $currentSort = ($orderBy == $columnName) ? $direction : null;
                $column['currentSortDirection'] = $currentSort;
                $sortUrlParams = 'orderBy=' . $columnName;
                
                
                $sortUrlParams .= ($currentSort && $currentSort != 'desc') ? '&direction=desc' : '&direction=asc';
                
                    
                $column['sortUrlParams'] = $sortUrlParams;
                
                $columns[] = $column;
            }
        }

        return $columns;
    }
    
    public function listColumns( ) 
    {
        return array();
    }

    
	public function view()
	{
        if(!$this->isFeatureAvailable('view'))
        {
            return $this->featureNotAvailable('view');
        }

		$assign['item'] = $this->getMainObject( false );

		if( !is_object( $assign['item'] ) )
		{
			die( 'no object' ); 
		}
		
		return $assign;
	}

	public function edit()
    {
        $id = get($_GET, 'id');
        if(!$this->isFeatureAvailable('edit') && isPositiveInt($id))
        {
            return $this->featureNotAvailable('edit');
        }
        elseif(!$this->isFeatureAvailable('create') && $id == 0)
        {
            return $this->featureNotAvailable('create');
        }

        $assign['item'] = $this->getMainObject( true );

		if( !is_object( $assign['item'] ) )
		{
			die( 'no object' ); 
		}
		
		$assign['createNew'] = empty($assign['item']->id);
		$assign['namespace'] = '-' . md5( microtime() );
		return $assign;
    }

    public function featureNotAvailable($feature)
    {
        // TODO - implement html return
        die("Feature: "  . $feature . " is not available");
    }

    public function isFeatureAvailable($feature)
    {
        if(isset($this->features[$feature]) && $this->features[$feature] == true)
        {
            return true;
        }
    }
    
    public function isItemDeletable( $item )
    {
        return ($item && !empty($item->id));
    }    

	public function save()
    {
        $id = get($_GET, 'id');
        if(!$this->isFeatureAvailable('edit') && isPositiveInt($id))
        {
            return $this->featureNotAvailable('edit');
        }
        elseif(!$this->isFeatureAvailable('create') && $id == 0)
        {
            return $this->featureNotAvailable('create');
        }

        $item = $this->getMainObject( true );
		$item->variablesSave( array_merge( $_POST, $_FILES ), NULL, $this->saveMode );
		if( !empty( $_POST['returnOnSave'] ) )
		{
			leafHttp::redirect( $_POST['returnOnSave'] );
		}
		else
        {
            if($this->features['view'])
            {
                $this->addUrlPart( 'do', 'view' );
                $this->addUrlPart( 'id', $item->id );

                if( !empty( $_POST['listUrl'] ) )
                {
                    $this->addUrlPart( 'listUrl', urlencode( $_POST['listUrl'] ) );
                }
            }
            elseif(!empty($_POST['listUrl']))
            {
                leafHttp::redirect($_POST['listUrl']);
            }
		}
	}

	public function delete()
	{
        if(!$this->isFeatureAvailable('delete'))
        {
            return $this->featureNotAvailable('delete');
        }

        
        $item = $this->getMainObject( false );

        $returnUrl = get($_POST, 'returnOnDelete');
        if (!$returnUrl)
        {
            $returnUrl = get($_POST, 'returnUrl');
        }
        $listUrl = get($_POST, 'listUrl');

            
		if ($this->isItemDeletable($item) && (!empty( $_POST['confirm'])))
		{
    
			$item->delete();
			if ( $returnUrl )
			{
				leafHttp::redirect( $returnUrl );
			}
            
			// $this->addUrlPart('do', 'all');
		}
		else
		{
			$url = getObject( 'requestUrl' );
			$url->reset();
			$url->addModifier( 'do=confirmDelete' );
			if ( $returnUrl )
			{
                // where to return after deletion
				$url->addModifier( 'returnUrl=' . urlencode( $returnUrl ) );
			}
            
            if ($listUrl)
            {
                // where to return 
                $url->addModifier( 'listUrl=' . urlencode( $listUrl ) );
            }

			leafHttp::redirect( $url->getModifiedUrl() );
		}
	}

	public function confirmDelete()
	{
        if(!$this->isFeatureAvailable('delete'))
        {
            return $this->featureNotAvailable('delete');
        }

		$assign['item'] = $this->getMainObject( false );
        $assign['deletionAllowed'] = $this->isItemDeletable( $assign['item'] );        
		return $assign;
	}
	
	public function saveAndRespond()
	{
		$item = $assign['item'] = $this->getMainObject( true );
        
		$values = $_POST;
		$assign['_template'] = '../../' . __CLASS__ . '/templates/_empty';
		$item->setDieOnError( false );
		$result = $item->variablesSave( $values, NULL, false, true );
		if( $result === true )
		{
			$this->response['result'] = 'ok';
			$this->response['id']   = $item->id;
			$this->response['name'] = $item->getDisplayString();
			
			if( !empty( $_GET['returnEntireObject'] ) )
			{
				$this->response['item'] = $item->getEncodableRepresentation();
			}
		}
		else
		{
			$this->response['result']      = 'error';
            if ($result->isMultiErrorOn())
            {
        		$this->response['errors']   = $result->getErrors();
            }
            else
            {
        		$this->response['errorCode']   = $result->errorCode;
    			$this->response['errorFields'] = $result->errorFields;
            }
			
			$assign['processing'] = $result;
			$assign['_template'] = '../../' . __CLASS__ . '/templates/_message';
		}
		
		return $assign;
    }

	public function deleteAndRespond()
	{
		$item = getObject( $this->mainObjectClass, $_GET['id'] );
		$assign['_template'] = '../../' . __CLASS__ . '/templates/_empty';
		
		if( !is_object( $item ) )
		{
			$this->response['result'] = 'error';
			$this->response['message'] = 'objectNotFound';
		}
		elseif( !empty( $_POST['confirm'] ) )
		{
			$item->delete();
			$this->response['result'] = 'ok';
		}
		else
		{
			$this->response['result'] = 'error';
			$this->response['message'] = 'noConfirmation';
		}
		
		return $assign;
	}

	/**
	 * override for leaf_module's moduleTemplate() to fill aliases on html fragments (required on ajax calls)
	 */
	function moduleTemplate($template, $assigns = array())
	{
        $pathToTemplate = $this->pathTo( $template );
        
		$templateDir = dirname( $pathToTemplate );
        $templateBaseName = basename( $pathToTemplate, '.tpl' );

		$smarty = new leaf_smarty( $templateDir );
		$smarty->assign_by_ref('_module', $this);
		$smarty->assign($assigns);
		if(isset($this->options))
		{
			$smarty->assign('options', $this->options);
		}
		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
		$smarty->register_outputfilter(array('alias_cache', 'fillInAliases'));
		alias_cache::setContext( $smarty, 'admin:'. get_class( $this ) );
		alias_cache::setFallbackContext( $smarty, 'admin:'. get_parent_class( $this ) );

		return $smarty->Fetch($templateBaseName . '.tpl');
	}
	
	// methods used for select/create type inputs
	public function getMainObjectClass()
	{
		return $this->mainObjectClass;
	}
    
    public function getMainObject( $allowNew, $id = false )
    {
        if ($id === false)
        {
            $id = get($_GET, 'id');
        }
        
        if (empty($id))
        {
            $id = 0;
        }
        
        if (!$id && !$allowNew)
        {
            return null;
        }
        
        $object = getObject( $this->mainObjectClass, $id );
        if( !$object )
        {
            $listViewUrl = $this->getModuleUrl();
            leafHttp::redirect( $listViewUrl );
        }

        return $object;
    }
    
	// submenu functions
	
	public function getSubmenuGroupName()
	{
		if( is_null( $this->submenuGroupName ) )
		{
			$menu = self::getMenu();
			if( get_class( $this ) == __CLASS__ )
			{
				if( !empty( $_GET['submenuGroup'] ) && isset( $menu[ $_GET['submenuGroup'] ] ) )
				{
					$this->submenuGroupName = $_GET['submenuGroup'];
				}
			}
			else
			{
				foreach( $menu as $groupName => $group )
				{
					foreach( $group as $sectionName => $section )
					{
						foreach( $section as $moduleItem )
						{
							if( $moduleItem['moduleName'] == get_class( $this ) )
							{
								$this->submenuGroupName = $groupName;
								break 3;
							}
						}	
					}
				}
			}
			
			// return first if nothing found
			if( is_null( $this->submenuGroupName ) )
			{
				$keys = array_keys( $menu );
				$this->submenuGroupName = array_shift( $keys );
			}
		}
		
		return $this->submenuGroupName;
	}
	
	public function getSubMenu()
	{
		if ( empty( $this->menuSections ) )
		{
			$menu = self::getMenu();
			$groupName = $this->getSubmenuGroupName();
			$this->menuSections = $menu[ $groupName ];
			foreach( $this->menuSections as $sectionName => $section )
			{
				foreach( $section as $itemName => $moduleItem )
				{
					if( _admin_module_access( $moduleItem['moduleName'] ) == false )
					{
						$this->disableMenuItem( $itemName );
					}
				}
			}
            

		}
		

        
        
		return $this->menuSections;
	}
	
	public function redirectTo( $item )
	{
		foreach( $this->menuSections as $sectionName => $menuSection )
		{
			foreach( $menuSection as $itemName => $menuItem )
			{
				if( $itemName == $item )
				{
					leafHttp::redirect( $menuItem[ 'url' ] );
				}
			}
		}
	}
	
	public function isActiveMenuItem( $item )
	{
		if( $this->activeMenuItem == $item )
		{
			return true;
		}
		return false;
	}

	public function setActiveMenuItem( $item )
	{
		$this->activeMenuItem = $item;
	}

	public function isDisabledMenuItem( $item )
	{
        
		foreach( $this->menuSections as $sectionName => $menuSection )
		{
			foreach( $menuSection as $itemName => $menuItem )
			{
				if( $itemName == $item )
				{
					if( !empty( $this->menuSections[ $sectionName ][ $itemName ][ 'disabled' ] ) )
					{
						return true;
					}
				}
			}
		}
		return false;
    }

    public function hasEnabledItems($sectionName)
    {
        if(isset($this->menuSections[$sectionName]))
        {
            foreach($this->menuSections[$sectionName] as $item )
            {
                if(!isset($item['disabled']) || !$item['disabled'])
                {
                    return true;
                }
            }
        }
    }
	
	public function disableMenuItem( $item )
	{
		foreach( $this->menuSections as $sectionName => $menuSection )
		{
			foreach( $menuSection as $itemName => $menuItem )
			{
				if( $itemName == $item )
				{
					$this->menuSections[ $sectionName ][ $itemName ][ 'disabled' ] = true;
				}
			}
		}
	}
	
	// list view methods
	
	public function getListViewAction()
    {
        $action = null;
		if( !empty( $_COOKIE['leafBaseModule_listViewAction'] ) )
		{
			$action = $_COOKIE['leafBaseModule_listViewAction'];
        }


        
        $defaultAction = 'view';
        if (empty($this->features['view']) && !empty($this->features['edit']))
        {
            $defaultAction = 'edit';
        }
        
        $feature = ($action == 'confirmDelete') ? 'delete' : $action;
        

        if(
            !$action || !isset($this->features[$feature]) || !$this->features[$feature]
        )
        {
            $action = $defaultAction;
        }


		return $action;
	}

	// resource package loading
	
	/**
	 * utility method for quickly creating and reusing html, css and js blocks
	 * loads resources and returns a path to template, usage example:
     *      {include file=$_module->useWidget("calendar")}
     * extend loadResourcePack() method if additional resources are needed
	 * WARNING: do not use this in top-level content.tpl - resources will not be loaded
	 */
	public function useWidget( $name )
	{
		// load resource pack for extra resources 
		$this->loadResourcePack( $name );
		// auto-load css and js from appropriate folders
		if( file_exists( PATH . 'styles/' . $name . '.css' ) )
		{
			_core_add_css( WWW . 'styles/' . $name . '.css' );
		}
		if( file_exists( PATH . 'js/' . $name . '.js' ) )
		{
			_core_add_js( WWW . 'js/' . $name . '.js' );
		}
		// return a path to template file
		return PATH . 'blocks/' . $name . '.tpl';
	}
	
	/**
     * loads a set of .js and .css files
     */
	protected function loadResourcePack( $name )
	{
		switch( $name )
		{
			case 'slider':
				// js
				_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.slider.min.js' );
				// css
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.slider.css' );
			break;
			case 'draggable':
				_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.draggable.min.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.droppable.min.js' );
			break;
			case 'sortable':
				_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
                _core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.widget.min.js' );
                _core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.mouse.min.js');
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.sortable.min.js' );
			break;
			case 'selectable':
				// jquery core
				_core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
				// ui dependencies
                _core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
                _core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.widget.min.js');
                _core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.mouse.min.js');
                
				_core_add_js(SHARED_WWW . '3rdpart/jquery/ui/ui.selectable.min.js');
				
				// theme
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.selectable.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css' );
			break;
			case 'dialog':
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

				_core_add_css( SHARED_WWW . 'styles/leafDialog.css' );
			break;
			case 'richtext':
				// richtext
				_core_add_js( SHARED_WWW . '3rdpart/tinymce/tiny_mce.js' );
				_core_add_js( SHARED_WWW . '3rdpart/tinymce/jquery.tinymce.js' );
			case 'dateNavigation':
				// jquery core
				_core_add_js(  SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
				// ui dependencies
				_core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.datepicker.min.js' );
				// theme
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css' );
				_core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.datepicker.css' );
			break;
			case 'tabs':
                // js
                _core_add_js( SHARED_WWW . '3rdpart/jquery/jquery-core.js' );
                _core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.core.min.js' );
                _core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.widget.min.js' );
                _core_add_js( SHARED_WWW . '3rdpart/jquery/ui/ui.tabs.min.js' );
                // css
                _core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.core.css' );
                _core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.theme.css' );
                _core_add_css( SHARED_WWW . '3rdpart/jquery/themes/base/ui.tabs.css' );
			break;
		}
	}

	// accessors
	
	public function getItemsPerPage()
	{
		return $this->itemsPerPage;
	}
	
    //
    //
	public static function getIcon()
	{
        $icon = leaf_get( 'properties', __CLASS__ . 'Config', 'icon' );
        if(empty($icon))
        {
            $icon = 'modules/leafBaseModule/icon.png';
        }
        return $icon;
    }
	
	public static function getMenu()
	{
        $cachedMenu = leaf_get( 'properties', __CLASS__ . 'Config', 'cachedMenu' );
        if ($cachedMenu)
        {
            return $cachedMenu;
        }
            
        
        $menu = leaf_get( 'properties', __CLASS__ . 'Config', 'menu' );
        if(empty($menu))
        {
            return;
        }
		// expand shorthand syntax
		foreach( $menu as $groupName => $group )
		{
			foreach( $group as $sectionName => $section )
			{
				$expandedSection = array();
				foreach( $section as $key => $value )
				{
					$keyInExpanded = $key;
					$expandedItem = array
					(
						'moduleName' => NULL,
						'url' 		 => NULL,
					);
					if( is_int( $key ) && is_string( $value ) )
					{
						$keyInExpanded = $value;
						$expandedItem['moduleName'] = $value;
						$expandedItem['url'] = '?module=' . $value;
					}
					elseif( is_string( $key ) && is_string( $value ) )
					{
						$expandedItem['moduleName'] = $key;
						$expandedItem['url'] = $value;
					}
					elseif( is_array( $value ) )
                    {
                        if (!empty($value['hasBadge']))
                        {
                            $moduleName = get($value, 'moduleName');
                            if ($moduleName)
                            {
                                _core_load_module( $moduleName, null, false );
                                
                                $value['badgeHtml'] = $moduleName::getBadgeHtml();
                            }
                        }

						$expandedItem = $value;
					}
					$expandedSection[ $keyInExpanded ] = $expandedItem;
				}
				
				$menu[ $groupName ][ $sectionName ] = $expandedSection;
			}
		}
		
        leaf_set( array( 'properties', __CLASS__ . 'Config', 'cachedMenu'), $menu );
        
		return $menu;
    }

    public function getMessage()
    {
        if(is_null($this->message))
        {
            $message = leafFlash::get('message');
            if($message)
            {
                $this->message = array(
                    'level' => $message['level']
                );

                if(is_string($message['message']))
                {
                    $this->message['aliasCode'] = $message['message'];
                }
                leafFlash::remove('message');
            }

        }

        return $this->message;
    }

    public function setMessage($message, $level)
    {
        leafFlash::set('message', array(
            'level' => $level,
            'message' => $message
        ));
    }
    
    public static function getBadge()
    {
        return null; 
    }
    
    public static function getBadgeHtml()
    {
        
        $badge = static::getBadge();
        if (is_null($badge))
        {
            return null;
        }
        elseif (is_scalar( $badge ))
        {
            $badge = array
            (
                'content' => $badge,
                'class'   => null,
                'wrap'    => true,
                'escape'  => true
            );
        }
        
        if (!is_array($badge))
        {
            return null;
        }
            
        $content = array_key_exists('content', $badge) ?        $badge['content'] : null;
        $wrap    = array_key_exists('wrap',    $badge) ? (bool)    $badge['wrap'] : true;
        $escape  = array_key_exists('escape',  $badge) ? (bool)  $badge['escape'] : true;
        $class   = !empty($badge['class']) ?  ' ' . $badge['class']   : '';
        
        if (is_null($content))
        {
            return null;
        }
        
        if ($escape)
        {
            $content = htmlspecialchars( $content );
        }
        
        if ($wrap)
        {
            $content = '<span class="badge' . $class . '">' . $content . '</span>';
        }
        
        return $content;
    }
}
