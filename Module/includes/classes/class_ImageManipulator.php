<?php
class ImageManipulator {
	
	public function __construct($filename) {
		$this->orig_filename = $filename;
		$this->info = getimagesize($filename);
		$this->mime_type = $this->info['mime'];
		$this->setTypeFromConstant($this->info[2]);
		$createfunction = $this->createfunction;
		$this->image_resource = $createfunction($filename);
	}
	
	public function getOriginalWidth() {
		return $this->info[0];
	}
	
	public function getOriginalHeight() {
		return $this->info[1];
	}
	
	public function getInfo() {
		print_r($this);
	}
	
	public function isLandscape() {
		return $this->getOriginalWidth() > $this->getOriginalHeight() ? true : false;
	}
	
	public function rotate($degrees) {
		$this->image_resource = imagerotate($this->image_resource, $degrees, 0);
		return $this;
	}
	
	public function resizeMaxDimension($goal) {
		$scaling_factor = $goal / max(array(imagesx($this->image_resource), imagesy($this->image_resource)));
		$new_w = imagesx($this->image_resource) * $scaling_factor;
		$new_h = imagesy($this->image_resource) * $scaling_factor;
		return $this->resize($new_w, $new_h);
	}
	
	public function resize($goal_width = null, $goal_height = null) {
			if ($goal_width && !$goal_height) {
				$scaling_factor = $goal_width / imagesx($this->image_resource);
				$new_w = $goal_width;
				$new_h = imagesy($this->image_resource) * $scaling_factor;
			}
			else if ($goal_height && !$goal_width) {
				$scaling_factor = $goal_height / imagesy($this->image_resource);
				$new_w = imagesx($this->image_resource) * $scaling_factor;
				$new_h = $goal_height;
			}
			else if ($goal_width && $goal_height) {
				$new_w = $goal_width;
				$new_h = $goal_height;
			}

			if ($new_w && $new_h) {
				$this->dst_img = imagecreatetruecolor($new_w, $new_h);
				imagecopyresampled($this->dst_img, $this->image_resource, 0, 0, 0, 0, $new_w, $new_h, imagesx($this->image_resource), imagesy($this->image_resource));
				$this->image_resource = $this->dst_img;
			}
			
			return $this;
	}

	public function crop($origin_x, $origin_y, $width, $height) {
		$this->dst_img = imagecreatetruecolor($width, $height);
		imagecopyresampled($this->dst_img, $this->image_resource, 0, 0, $origin_x, $origin_y, $width, $height, $width, $height);
		$this->image_resource = $this->dst_img;
		return $this;
	}
	
	public function greyscale() {
		imagefilter($this->image_resource, IMG_FILTER_GRAYSCALE);
		return $this;
	}
	
	public function setType($type) {
		switch ($type) {
			case 'jpg':
				return $this->setTypeFromConstant(2);
			break;
		
			case 'png':
				return $this->setTypeFromConstant(3);
			break;
		
			case 'gif':
				return $this->setTypeFromConstant(1);
			break;
		}			
	}
	
	private function setTypeFromConstant($type) {
		$this->image_type = $type;
		switch ($type) {
			case 2:
				$this->createfunction = 'imagecreatefromjpeg';
				$this->outputfunction = 'imagejpeg';
				$this->extension = '.jpg';
			break;

			case 3:
				$this->createfunction = 'imagecreatefrompng';
				$this->outputfunction = 'imagepng';
				$this->extension = '.png';
			break;

			case 1:
				$this->createfunction = 'imagecreatefromgif';
				$this->outputfunction = 'imagegif';
				$this->extension = '.gif';
			break;
		
			case 6:
				$this->createfunction = 'imagecreatefromwbmp';
				$this->outputfunction = 'imagebmp';
				$this->extension = '.bmp';
			break;
		
			default:
			$this->getInfo();die();
			break;
		}
		
		return $this;
	}
	
	public function saveToFile($filename, $append_extension = true) {
		if ($append_extension)
			$filename .= $this->extension;
		
		$outputfunction = $this->outputfunction;
		$outputfunction($this->image_resource, $filename);
		imagedestroy($this->image_resource);
	}
}
?>