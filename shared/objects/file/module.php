<?
class file extends leaf_object_module{

	var $module_path='modules/files/';
	var $file_mode=0666;
	var $die_on_error = true;

	var $object_type=21;

	var $options=array(
		'allowed'=>array('*'),
		'denied'=>array(),
	);

	var $file_types=array(
		'jpg'=>'image',
		'gif'=>'image',
		'png'=>'image',
		'jpeg'=>'image',
		'swf'=>'image',
	);

	var $upload_errors = array(
		1 => 'The uploaded file exceeds the upload_max_filesize directive',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		5 => 'Missing a temporary folder',
		6 => 'Failed to write file to disk'
	);

	var $available_configs = array(
		'thumbnail_resize_options',
		'allowed_types',
		'denied_types',
	);

    var $allowedLocalImportModes = array('copy', 'move');
	var $localImportMode = null;

	function _typeInit(){
		//default
		$this->options['check_order'] = 1;
		//image resize options
		if(!empty($this->_config['image_resize_options']) && is_array($this->_config['image_resize_options']))
		{
			$this->options['resize'] = $this->_config['image_resize_options'];
		}
		//thumbnail resize options
		if(!empty($this->_config['thumbnail_resize_options']))
		{
			$thumbnail_resize_options=str_replace(';',',', $this->_config['thumbnail_resize_options']);
			$this->options['thumbnail']=explode(',',$thumbnail_resize_options);
		}
		//allowed extensions
		if(!empty($this->_config['allowed_types']))
		{
			$tmp=str_replace(';',',',$this->_config['allowed_types']);
			$this->options['allowed']=explode(',',$tmp);
		}
		//denied extensions
		if(!empty($this->_config['denied_types']))
		{
			$tmp=str_replace(';',',',$this->_config['denied_types']);
			$this->options['denied']=explode(',',$tmp);
		}
		if(!empty($this->_config['check_order']))
		{
			$this->options['check_order'] = $this->_config['check_order'];
		}
		//check write permission for file directory
		if(!is_writable($this->_config['files_path']))
		{
			die($this->_config['files_path'] . ' is not writable');
		}
	}

	function _checkFileExtension($extension){
		$extension = mb_strtolower($extension);
		//allow/denied
		if($this->options['check_order'] == 1)
		{
			//allow all > check denied
			if(in_array('*',$this->options['allowed']) && (!sizeof($this->options['denied']) || (!in_array($extension,$this->options['denied']) && !in_array('*',$this->options['denied']))))
			{
				return true;
			}
			//check allow
			else if(in_array($extension,$this->options['allowed'])){
				return true;
			}
		}
		//denied/allow
		else
		{
			//allow all > check denied
			if(!sizeof($this->options['denied']) && (in_array('*',$this->options['allowed']) || in_array($extension,$this->options['allowed'])))
			{
				return true;
			}
			//denied all
			else if(in_array('*',$this->options['denied'])){
				return false;
			}
			//check denied & allow
			if(!in_array($extension,$this->options['denied']) && (in_array('*',$this->options['allowed']) || in_array($extension,$this->options['allowed'])))
			{
				return true;
			}
		}
		return false;
	}

	function get_random_name($name){
		return $name.'_'.substr(md5(time() . rand(1, 10000)),0,5);
	}

	function _uploadFile($file_path, $file_name, $http_upload = true){
		//delete old file
		if(!empty($this->object_data['data']['file_name']) && is_file($this->_config['files_path'] . $this->object_data['data']['file_name']))
		{
			//delete old image
			unlink($this->_config['files_path'] . $this->object_data['data']['file_name']);
			unset($this->object_data['data']['extra_info']['resize']);
			unset($this->object_data['data']['extra_info']['thumbnail_size']);
			unset($this->object_data['data']['extra_info']['thumbnail']);
			unset($this->object_data['data']['extra_info']['pluginsData']);
			//delete old thumb
			if(is_file($this->thumbFilePath = $this->_config['files_path'] . 'thumb_' . $this->object_data['data']['file_name']))
			{
				unlink($this->thumbFilePath);
			}
		}
        $path_parts = pathinfo(mb_strtolower($file_name));
		$basename = stringToLatin($path_parts['filename'], true);
        $target_name = $basename;

        if(!empty($path_parts['extension']))
        {
            $target_name .= '.' . $path_parts['extension'];
        }

		//get right name
		while(is_file($this->_config['files_path'] . $target_name))
		{
            $target_name = $this->get_random_name($basename);
            if(!empty($path_parts['extension']))
            {
                $target_name .= '.' . $path_parts['extension'];
            }
		}
		$target_path = $this->_config['files_path'] . $target_name;
		//move/copy file
		if($http_upload)
		{
			move_uploaded_file($file_path, $target_path);
			chmod($target_path, $this->file_mode);
		}
		else
		{
			$chmodEnable = @chmod($file_path, $this->file_mode);
			$method = $this->getLocalImportMode();
			if (
				$chmodEnable == true
				&&
				($method == 'move')
				&&
				(is_writable($file_path))
            )
		    {
				// rename
		        copy($file_path, $target_path);
		    }
		    else
		    {
		        // method == copy OR file is not writable - do the copy!
                copy($file_path, $target_path);
				// chmod file
				chmod($target_path, $this->file_mode);
				// try to delete
				if($method == 'move' && is_writable($file_path))
				{
					unlink($file_path);
				}
		    }
		}

		$this->object_data['data']['file_name'] = $target_name;
		$this->object_data['data']['original_name'] = basename($file_name);
		$this->object_data['data']['extension'] = !empty($path_parts['extension']) ? $path_parts['extension'] : '';
	}

	function _typePreSave($params){
		//check for file array
		if(isset($params['file']['error']))
		{
			$params['tmp_name'] = $params['file']['tmp_name'];
			$params['error'] = $params['file']['error'];
			$params['upload_name'] = $params['file']['name'];
		}

		if(!empty($params['source_file']))
		{
			$http_upload = false;
			$path_parts = pathinfo($params['source_file']);
		}
		else if(!empty($params['tmp_name']) && isset($params['error']))
		{
			$params['source_file'] = $params['tmp_name'];
			$params['target_file'] = !empty($params['upload_name']) ? $params['upload_name'] : $params['name'];
			if (!isset($params['http_upload_copy']))
			{
                $http_upload = true;
			}
			else
			{
			    $http_upload = false;
			}

			$path_parts = pathinfo($params['target_file']);
		}
		elseif(!empty($params['tmp_name']) && isset($params['tmp_name']))
		{
			trigger_error('file upload error', E_USER_ERROR);
		}

		if(!empty($params['source_file']))
		{
			//check for php upload error
			if($http_upload && $params['error'] > 0)
			{
				if($this->die_on_error)
				{
					die($this->upload_errors[$params['error']]);
				}
				else
				{
					return false;
				}
			}
			if(!empty($path_parts['extension']) && !$this->_checkFileExtension($path_parts['extension']))
			{
				if($this->die_on_error)
				{
					die('this file type is not allowed: ' . $path_parts['extension']);
				}
				else
				{
					return false;
				}
			}
			if(empty($params['target_file']))
			{
				$params['target_file'] = $path_parts['basename'];
			}
			$this->_uploadFile($params['source_file'], $params['target_file'], $http_upload);
		}
		return $params;
	}

	function _typeSave($params, $save_values = null){
        if(empty($this->object_data['data']['file_name']))
        {
            return true;
        }

		$this->filePath = $this->_config['files_path'] . $this->object_data['data']['file_name'];
		$this->thumbFilePath = $this->_config['files_path'] . 'thumb_' . $this->object_data['data']['file_name'];

		if (!empty($params['skipPlugins']))
		{
		    $this->object_data['data']['extra_info']['skipPlugins'] = $params['skipPlugins'];
		}
		//update object info
		if
		(
			$this->object_data['data']['extension'] == 'jpg' || 
			$this->object_data['data']['extension'] == 'jpeg' ||
			$this->object_data['data']['extension'] == 'png' ||
			$this->object_data['data']['extension'] == 'gif'
		)
		{
			if( isset( $params['altText'] ) )
			{
    			$this->object_data['data']['extra_info']['altText'] = $params['altText'];
			}
			
			//resize
			if(
				!empty($params['resize'])
				&&
				(
					empty($this->object_data['data']['extra_info']['resize'])
					||
					$this->object_data['data']['extra_info']['resize'] != $params['resize']
				)
			)
			{
				$resize = explode('x', $params['resize']);
				$resizePlugin = new imageResize();
				if (isset($params['quality']))
				{
				    $resizePlugin->setQuality( $params['quality'] );
				}
				$pluginParams = array(
					'width' => $resize[0],
					'height' => $resize[1],
				);
				$resizePlugin->processInput($this->filePath, $pluginParams);
				$this->object_data['data']['extra_info']['resize'] = $params['resize'];
			}
			elseif(empty($params['resize']))
			{
				unset($this->object_data['data']['extra_info']['resize']);
			}
			if(!empty($params['crop']))
			{
				$dimensions = explode('x', $params['crop']);
				$pluginParams = array(
					'width' => $dimensions[0],
					'height' => $dimensions[1],
				);
				if($params['crop_mode'])
				{
					$pluginParams['mode'] = $params['crop_mode'];
				}
				$crop = new imageCrop();
				if (isset($params['quality']))
				{
				    $crop->setQuality( $params['quality'] );
				}
				$crop->processInput($this->filePath, $pluginParams);
			}

			if (!empty($params['resize_and_crop']))
			{
				$dimensions = explode('x', $params['resize_and_crop']);
				$pluginParams = array(
					'width'  => $dimensions[0],
					'height' => $dimensions[1],
				);
				$resizeAndCrop = new imageResizeAndCrop();
				if (isset($params['quality']))
				{
				    $resizeAndCrop->setQuality( $params['quality'] );
				}
				$resizeAndCrop->processInput($this->filePath, $pluginParams);
			}


			// make thumbnail
			if(
				!empty($params['thumbnail'])
				&&
				(
					empty($this->object_data['data']['extra_info']['thumbnail_size'])
					||
					$this->object_data['data']['extra_info']['thumbnail_size'] != $params['thumbnail']
				)
			)
			{
			    // select thumbnail mode
			    $thumbnailModes = array(
                    'resize' => 'imageResize',
                    'crop_center' => 'imageCrop'
                );
			    if (
			         (empty($params['thumbnail_mode']))
			         ||
			         (!isset($thumbnailModes[$params['thumbnail_mode']]))
			    )
			    {
			        $thumbNailMode = key($thumbnailModes);
			    }
			    else
			    {
			        $thumbNailMode = $params['thumbnail_mode'];
			    }

				$thumbnail = explode('x', $params['thumbnail']);
				$pluginParams = array(
					'width' => $thumbnail[0],
					'height' => $thumbnail[1],
					'targetFile' => $this->thumbFilePath
				);
				$thumbnailPluginClass = $thumbnailModes[$thumbNailMode];
				$thumbnailPlugin = new $thumbnailPluginClass();
				if ($thumbnailPlugin)
				{
				    $thumbnailPlugin->processInput($this->filePath, $pluginParams);
    				$tmp = getimagesize($this->thumbFilePath);
	   			    $this->object_data['data']['extra_info']['thumbnail_size'] = $params['thumbnail'];
				    $this->object_data['data']['extra_info']['thumbnail']['width'] = $tmp[0];
				    $this->object_data['data']['extra_info']['thumbnail']['height'] = $tmp[1];
				}
				chmod($this->thumbFilePath, $this->file_mode); 
			}
			//delete existing
			elseif(empty($params['thumbnail']))
			{
				if(is_file($this->thumbFilePath))
				{
					unlink($this->thumbFilePath);
				}
				unset($this->object_data['data']['extra_info']['thumbnail_size']);
				unset($this->object_data['data']['extra_info']['thumbnail']);
			}

		}
		//update
		if(isset($this->file_types[$this->object_data['data']['extension']]) && $this->file_types[$this->object_data['data']['extension']] == 'image')
		{
			$tmp = getimagesize($this->filePath);
			$this->object_data['data']['extra_info']['image_width']=$tmp[0];
			$this->object_data['data']['extra_info']['image_height']=$tmp[1];
		}
		// update file table
		$this->updateFileData(true);
		//watermark
		$plugins = !empty($this->_config['plugins']) ? $this->_config['plugins'] : array();
		if(!empty($this->options['watermark']) && file_exists($this->options['watermark']))
		{
			$plugins['imageWatermark']= array(
				'watermarkFile' => $this->options['watermark'],
				'applyOn' => array('*.jpg', '*.png', '*.gif')
			);
		}

		//secure
		if(!empty($plugins))
		{
			foreach($plugins as $pluginKey => $pluginData)
			{
				if(is_array($pluginData))
				{
					$pluginName = $pluginKey;
				}
				else
				{
					$pluginName = $pluginData;
					$pluginData = NULL;
				}
				$this->applyPlugin($pluginName, $pluginData);
			}
		}
		
		if(isset($params['fileobjectPlugins']))
		{
			foreach($params['fileobjectPlugins'] as $pluginName)
			{
				if($plugin = leaf_get('properties', 'filePlugins', $pluginName))
				{
					$this->applyPlugin($plugin['plugin'], $plugin);
				}
			}
		}
		
		return true;
	}

	function fnmatch($pattern, $string) {
		for ($op = 0, $npattern = '', $n = 0, $l = strlen($pattern); $n < $l; $n++) {
			switch ($c = $pattern[$n]) {
				case '\\':
				$npattern .= '\\' . @$pattern[++$n];
				break;
				case '.': case '+': case '^': case '$': case '(': case ')': case '{': case '}': case '=': case '!': case '<': case '>': case '|':
				$npattern .= '\\' . $c;
				break;
				case '?': case '*':
				$npattern .= '.' . $c;
				break;
				case '[': case ']': default:
				$npattern .= $c;
				if ($c == '[') {
				$op++;
				} else if ($c == ']') {
				if ($op == 0) return false;
				$op--;
				}
				break;
			}
		}
		if ($op != 0) return false;
		return preg_match('/' . $npattern . '$/i', $string);
	}

	function applyPlugin($pluginName, $pluginData = NULL){
		$files = array();

		if (!empty($this->object_data['data']['extra_info']['skipPlugins']))
		{
		    if (in_array($pluginName, $this->object_data['data']['extra_info']['skipPlugins']))
		    {
                return; // skip this plugin
		    }
		}

		if(file_exists($this->thumbFilePath) && $pluginName != 'imageWatermark')
		{
			$include = false;
			if(!empty($pluginData['applyOn']))
			{
				$file = basename($this->thumbFilePath);
				foreach($pluginData['applyOn'] as $exp)
				{
					if(!$this->fnmatch($exp, $file))
					{
						$include = true;
						break;
					}
				}
			}
			
			if($include)
			{
				$files[] = $this->thumbFilePath;
			}
		}
		if(file_exists($this->filePath))
		{
			$include = false;
			if(!empty($pluginData['applyOn']))
			{
				$file = basename($this->filePath);
				foreach($pluginData['applyOn'] as $exp)
				{
					if($this->fnmatch($exp, $file))
					{
						$include = true;
						break;
					}
				}
			}
			if($include)
			{
				$files[] = $this->filePath;
			}
		}
		if(empty($files))
		{
			return;
		}


		if 
		(
            (empty($this->object_data['data']['extra_info']['pluginsData'][$pluginName])) // no plugins data
            ||
            (!$this->object_data['data']['extra_info']['pluginsData'][$pluginName]) // not already processed
        )
		{
			$plugin = new $pluginName();
			foreach($files as $file)
			{
				$plugin->processInput($file, $pluginData);
			}
			if( isset( $plugin->overrideFileFormat ) )
			{
				$this->updateFileFormat( $plugin->overrideFileFormat );
			}
			$this->object_data['data']['extra_info']['pluginsData'][$pluginName] = true;
			$this->updateFileData();
		}
	}
	
	function updateFileFormat( $extension = NULL )
	{
		if( $extension !== NULL )
		{
			$this->object_data['data']['extension'] = $extension;
			$parts = explode( '.', $this->object_data['data']['file_name'] );
			$parts[ count($parts) - 1 ] = $extension;
			$this->object_data['data']['file_name'] = implode( '.', $parts );
		}
	}

	function updateFileData($checkNewObject = false){
		$saveData = $this->object_data['data'];
		$update_values = array('extension', 'file_name', 'original_name', 'extra_info');
		$values = array();
		foreach($update_values as $key)
		{
			if(isset($saveData[$key]))
			{
				$values[$key] = $saveData[$key];
			}
		}
		$values['object_id'] = $this->object_data['id'];
		dbReplace('files', $values);
	}

	function _assignObjectData(){
		$q = '
		SELECT
			files.*
		FROM
			`files`
		WHERE
			files.object_id = "' . $this->object_data['id'] . '"
		';
		if(!($this->object_data['data'] = dbGetRow($q)))
		{
			$this->object_data['data']['extra_info'] = array();
		}
		else
		{
			$this->object_data['data']['extra_info'] = unserialize($this->object_data['data']['extra_info']);
			$this->object_data['data']['file_path'] = $this->_config['files_path'] . $this->object_data['data']['file_name'];
			$this->object_data['data']['file_www'] = $this->_config['files_www'] . $this->object_data['data']['file_name'];
			if(!empty($this->object_data['data']['file_name']))
			{
				$size = @filesize($this->object_data['data']['file_path']);
				$this->object_data['data']['file_size'] = $this->fsize($size);
			}
		}
	}

	function _typeEdit($params){
		_core_add_js($this->module_www . 'functions.js');
		_core_add_css($this->module_www . 'style.css');
		//add non-standar thumb/resize options
		if(!empty($this->options['resize']) && isset($this->object_data['data']['extra_info']['resize']) && !in_array($this->object_data['data']['extra_info']['resize'], $this->options['resize']))
		{
			$this->options['resize'][$this->object_data['data']['extra_info']['resize']] = $this->object_data['data']['extra_info']['resize'];
		}
		if(isset($this->object_data['data']['extra_info']['thumbnail_size']) && !in_array($this->object_data['data']['extra_info']['thumbnail_size'], $this->options['thumbnail']))
		{
			$this->options['thumbnail'][$this->object_data['data']['extra_info']['thumbnail_size']] = $this->object_data['data']['extra_info']['thumbnail_size'];
		}
		$assign['options'] = $this->options;
		$assign['file_types'] = $this->file_types;
        
		return $this->editForm($assign);
	}

	function _typeDelete(){
		$q = '
		SELECT
			`file_name`
		FROM
			`files`
		WHERE
			`object_id` = "' . $this->object_data['id'] . '"
		';
		$file = dbGetRow($q);
		//delete thumbnail
		if(is_file($this->_config['files_path'] . 'thumb_' . $file['file_name']))
		{
			unlink($this->_config['files_path'] . 'thumb_' . $file['file_name']);
		}
		//delete file
		if(is_file($this->_config['files_path'] . $file['file_name']))
		{
			unlink($this->_config['files_path'] . $file['file_name']);
		}
		//delete from table
		$q = '
		DELETE
		FROM
			`files`
		WHERE
			`object_id` = "' . $this->object_data['id'] . '"
		';
		dbQuery($q);
	}

	function _typeCopy(){
		$object_id = $this->object_data['old_id'];
		$object2_id = $this->object_data['id'];
		$file = dbGetRow('SELECT * FROM files WHERE object_id="'.$object_id.'"');
		$file_name = mb_strtolower($file['original_name']);
		$filePath = $this->_config['files_path'] . $file['file_name'];
		if(!empty($file_name) && file_exists($filePath))
		{
			$path_parts = pathinfo($file_name);
			//copy file
			$target_name = basename ($path_parts['basename'], '.' . $path_parts['extension']);
			$basename = stringToLatin($target_name, true);
			$target_name = $basename . '.' . $path_parts['extension'];
			//get right name
			while(is_file($this->_config['files_path'].$target_name))
			{
				$target_name=$this->get_random_name($basename).'.'.$path_parts['extension'];
			}
			copy($filePath, $this->_config['files_path'] . $target_name);
			chmod($this->_config['files_path'].$target_name,$this->file_mode);
			//copy thumbnail
			if(is_file($thumbnail=$this->_config['files_path'].'thumb_'.$file['file_name']))
			{
				copy($thumbnail,$this->_config['files_path'].'thumb_'.$target_name);
				chmod($this->_config['files_path'].'thumb_'.$target_name,$this->file_mode);
			}
			//copy table info
			$fields = array(
				'object_id' => $object2_id,
				'original_name' => $file['original_name'],
				'extension' => $file['extension'],
				'file_name' => $target_name,
				'extra_info' => $file['extra_info'],
			);
			dbReplace('files', $fields);
		}
	}

	function fsize($size) {
		   $a = array("B", "KB", "MB", "GB", "TB", "PB");
		   $pos = 0;
		   while ($size >= 1024) {
				   $size /= 1024;
				   $pos++;
		   }
		   return round($size,2)." ".$a[$pos];
	}

    function _typeView($params = null, & $module = null){
        if(!empty($this->object_data['data']['file_name']))
        {
            if(isset($_GET['thumb']) && is_file(PATH . 'files/thumb_' . $this->object_data['data']['file_name']))
            {
                header('Location: ' . WWW . 'files/thumb_' . $this->object_data['data']['file_name']);
            }
            else
            {
                header('Location: ' . WWW . 'files/' .  $this->object_data['data']['file_name']);
            }
        }
		exit;
	}

	function setLocalImportMode($mode)
	{
	    $allowedModes = $this->allowedLocalImportModes;
	    if (!in_array($mode,$allowedModes))
	    {
	        $mode = current($allowedModes);
	    }
	    $this->localImportMode = $mode;
	}

	function getLocalImportMode()
	{
	    $allowedModes = $this->allowedLocalImportModes;
	    $mode = $this->localImportMode;
	    if (!in_array($mode,$allowedModes))
	    {
	        $mode = current($allowedModes);
	    }
	    return $mode;

	}
	
	
	// file objects cannot be related
	public function isInAnyRelation()
	{
		return false;
	}
	public function canBeInRelation()
	{
		return false;
	}
}
?>
