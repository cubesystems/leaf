<?
class objectManager extends leafComponent{

	protected $baseUrl;
	protected $contentBaseUrl = '../';
	protected $imageExtensions = array('jpg', 'gif', 'png');

	public function buildDialog($type){
		_core_add_js('js/xmlhttp.js');
		_core_add_js($this->objectUrl . 'functions.js');
		_core_add_css($this->objectUrl . 'style.css');
		_core_add_js(SHARED_WWW . '3rdpart/xinha/popups/popup.js');
		//access & input error check
		/*
		if($this->textarea || $this->target_name){
			$insert_image=true;
		}
		else
			$insert_image=false;
		*/
		
		if(!empty($_GET['getNodeTree']) && $type != "newLinks")
		{
			die($this->getObjectTree($_GET['getNodeTree']));
		}
		if(!empty($_GET['suggest']))
		{
			$this->getSuggests($_GET['suggest']);
		}
		if(!empty($_GET['setContentBaseUrl']))
		{
			die($this->contentBaseUrl);
		}
		if(!empty($_GET['setImageExtensions']))
		{
			die(implode(';',$this->imageExtensions));
		}
		if(!empty($_GET['setImageDir']))
		{
			die(leaf_get('objects_config', 21, 'files_www'));
		}
		if($type == "link")
		{
			$template = 'viewLinks';
		}
		else if($type == "newLinks")
		{
			$template = "newLinks";
		}
		else
		{
			$type = 'image';
			$assign['imageDir'] = leaf_get('objects_config', 21, 'files_www');
			$template = 'viewImages';
		}
		if($contentBaseUrl = getValue('components.objectManager.contentBaseUrl'))
		{
			$this->contentBaseUrl = $contentBaseUrl;
		}
		
		$assign['group_id'] = 0;
		$assign['type'] = $type;
		$assign['baseUrl'] = clear_query_string(array(), false);
		
		if($type == "newLinks")
		{
			$assign['template'] = $template;
			$obj = 0;
			$assign['firstLoad'] = true;
			if (!empty($_GET['getNodeTree']))
			{
				$obj = $_GET['getNodeTree'];
				$assign['firstLoad'] = false;
			}
			$data = $this->getObjectTree($obj, 'data');
			foreach($data as $key => $value)
			{
				$assign[$key] = $value;
			}
			die($this->buildOutput($template, $assign));
		}
		else 
		{
			$assign['objectTree'] = $this->getObjectTree(0);
		}
		return $this->buildOutput($template, $assign);
	}

	public function setBaseUrl($url){
		$this->baseUrl = $url;
		_core_add_js($this->objectUrl . 'functions.js');
		_core_add_css($this->objectUrl . 'style.css');
	}

	public function getImage($object){
		debug($object);
	}

	public function getObjectTree($root, $return = 'normal'){
        $assign['root'] = $root;
        $assign['objects'] = objectTree::getChildren($root);
        $this->setBaseUrl(clear_query_string(array(), false));
		if($return == 'data')
		{
			return $assign;
		}
		return $this->buildOutput('nodeTree', $assign);
	}

	public function getSuggests($suggest){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
		header("Cache-Control: no-cache, must-revalidate" );
		header("Pragma: no-cache" );
		header("Content-Type: text/xml; charset=utf-8");
		$q = '
		SELECT
			o.id,
			o.name,
			f.file_name
		FROM
			`files` `f`
		LEFT JOIN
			`objects` `o` ON o.id = f.object_id
		WHERE
			f.extension IN ("' . implode('","', $this->imageExtensions) . '") AND
			o.name LIKE ("%' . dbSE($suggest) . '%")
		ORDER BY
			o.name
		';
		$r = dbQuery($q);
		while($item = $r->fetch())
		{
			echo '<div><a onclick="return pickObject(this, \'' . $item['file_name'] . '\')" name="' . $item['id'] . '" href="#">' . htmlspecialchars($item['name']) . '</a></div>';
		}
		exit;
	}

	public function isImage($object){
		$file_types=array(
			'jpg'=>'image',
			'gif'=>'image',
			'png'=>'image',
			'jpeg'=>'image',
		);
		if($object['type'] == 21)
		{
			if(isset($file_types[dbGetOne('SELECT extension FROM files WHERE object_id="'.$object['id'].'"')]))
			{
				return true;
			}
		}
	}
}
?>
