<?
class leafComponent extends leafBaseObject{
	protected $objectPath, $objectUrl;
	public function initEnvironment()
	{
		$this->initPaths();
	}
	
	/* does not need autoload functionality */
    public static function _autoload( $className ){}

	private function initPaths()
	{
		$className = get_class($this);
		$classPath = getValue('custom_apis.' . $className);

		$location = $classPath;


        // remove custom apis path from path
		$customApisPath        = realpath(SHARED_PATH . 'custom_apis') . DIRECTORY_SEPARATOR;
		// convert an exact path string, e.g. /shared/custom_apis/ or \shared\custom_apis\
		// to a regexp matching both types of slashes: /(\/|\\\\)shared(\/|\\\\)custom_apis(\/|\\\\)/
		$customApisPathPattern = '/' . preg_replace('/(\/|\\\\)/', '(\/|\\\\\\\\)+', $customApisPath) . '/';
		$location = preg_replace($customApisPathPattern, '', $location);


        // remove file name from path
		$fileNamePattern = '/(\/|\\\\)' . $className . '\.class\.php/';
		$location = preg_replace($fileNamePattern, '', $location);


        // split location
		$locationParts = preg_split('/(\/|\\\\)/', $location, null, PREG_SPLIT_NO_EMPTY);


		$this->objectPath = realpath(SHARED_PATH . 'custom_apis') . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $locationParts) . DIRECTORY_SEPARATOR;
		$this->objectUrl = SHARED_WWW . 'custom_apis/' . implode('/', $locationParts) . '/';
	}

	public function buildOutput($template, $assigns = array()){
		$smarty = new leaf_smarty($this->objectPath .  'templates' . DIRECTORY_SEPARATOR);
		$smarty->assign_by_ref('_component', $this);
		$smarty->assign($assigns);
		if(isset($this->options))
		{
			$smarty->assign('options', $this->options);
		}
		return $smarty->Fetch($template . '.tpl');
	}
}
?>
