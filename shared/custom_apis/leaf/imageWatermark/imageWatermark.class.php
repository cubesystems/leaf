<?
class imageWatermark extends imageBase{

	protected $watermarkPosition = 'right bottom';

	public function processInput($sourceResource, $params = array()){
		parent::processInput($sourceResource, $params);
		if(empty($params['watermarkFile']))
		{
			trigger_error('no watermark file', E_USER_ERROR);
		}
		else
		{
			$watermarkFile = $params['watermarkFile'];
		}
		if(!file_exists($params['watermarkFile']))
		{
			trigger_error('unexisting watermark file: ' . $params['watermarkFile'], E_USER_ERROR);
		}
        
        if(!empty($params['watermarkPosition']))
        {
            $this->watermarkPosition = $params['watermarkPosition'];
        }
        
		//watermark
		$allowed_watermarks = array(
			'png' => 'imagecreatefrompng',
			'gif' => 'imagecreatefromgif',
		);
		$path_parts = pathinfo($watermarkFile);
		$path_parts['extension'] = strtolower($path_parts['extension']);
		if(!array_key_exists($path_parts['extension'], $allowed_watermarks))
		{
			die('unsuported watermark file');
		}

		$position = explode(' ', $this->watermarkPosition);

		$horizontal_allowed = array('left', 'right', 'center');
		$vertical_allowed = array('top', 'bottom', 'middle');
		
		$horizontal_offset = isset($position[2]) && is_numeric($position[2]) ? $position[2] : 0;
		$vertical_offset = isset($position[3]) && is_numeric($position[3]) ? $position[3] : 0;
		//parse first param
		if(!empty($position[0]) && in_array($position[0], $horizontal_allowed))
		{
			$horizontal_pos = $position[0];
		}
		elseif(!empty($position[0]) && in_array($position[0], $vertical_allowed))
		{
			$vertical_pos = $position[0];
		}
		//parse second param
		if(!empty($position[1]) && in_array($position[1], $horizontal_allowed))
		{
			$horizontal_pos = $position[1];
		}
		elseif(!empty($position[1]) && in_array($position[1], $vertical_allowed))
		{
			$vertical_pos = $position[1];
		}
		//set default variables
		if(empty($horizontal_pos))
		{
			$horizontal_pos = 'right';
		}
		if(empty($vertical_pos))
		{
			$vertical_pos = 'bottom';
		}
		
		$f_name = $allowed_watermarks[$path_parts['extension']];
		$watermarkfile_id = $f_name($watermarkFile);
		imagealphablending($this->source, true);
		$watermark_width = imagesx($watermarkfile_id);
		$watermark_height = imagesy($watermarkfile_id);
		$image_width = imagesx($this->source);
		$image_height = imagesy($this->source);
		//parse horizontal position
		if($horizontal_pos == 'left')
		{
			$dest_x = $horizontal_offset;
		}
		elseif($horizontal_pos == 'right')
		{
			$dest_x = $image_width - $watermark_width - $horizontal_offset;
		}
		elseif($horizontal_pos == 'center')
		{
			$dest_x = $image_width/2 - $watermark_width/2;
		}
		//parse vertical position
		if($vertical_pos == 'top')
		{
			$dest_y = $vertical_offset;
		}
		elseif($vertical_pos == 'bottom')
		{
			$dest_y = $image_height - $watermark_height - $vertical_offset;
		}
		elseif($vertical_pos == 'middle')
		{
			$dest_y = $image_height/2 - $watermark_height/2;
		}
		$this->image = $this->source;
		imagecopy($this->image, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
		imagedestroy($watermarkfile_id);
		return $this->returnResource();
	}
	
}
?>