<?
class leaf_error{
	
	var $messages = array();
	var $headers = array();
	var $css = array();
	var $title = NULL;
	
	function leaf_error(){
		$this->addStyle(SHARED_WWW . 'classes/leaf_error/style.css');
	}

	function fetch(){
		$template = new leaf_smarty(dirname(__FILE__));
		require_once(SHARED_PATH . 'classes/smarty_plugins/alias_cache.class.php');
		$template->register_outputfilter(array('alias_cache', 'fillInAliases'));
		$template->assign_by_ref('object', $this);
		return $template->fetch('page.tpl');
	}
	
	function display()
    {
        if (php_sapi_name() == 'cli')
        {
            $errorText = '';
            foreach ($this->messages as $message)
            {
                $message = get($message, 'msg');
                if (!$message)
                {   
                    $message = strip_tags( get($message, 'html') );
                }
                
                $errorText .= 'Leaf error: ' . $message . PHP_EOL;
            }
            file_put_contents('php://stderr', $errorText);
        }
        else
        {
    		echo $this->fetch();
        }
        die();
	}
	
	function addStyle($css){
		$this->css[] = $css;
	}

	function addHeader($header){
		$this->headers[] = $header;
	}

	function addMessage($message)
    {
		if (is_string($message))
		{
			$message = array('msg' => $message);
		}
        
		if (!empty($message['header']) && $this->title === NULL)
		{
			$this->title = $message['header'];
		}
                
		$this->messages[] = $message;
	}
	
}
?>