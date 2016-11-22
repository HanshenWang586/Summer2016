<?
class FileTools extends CMS_Class {
	/**
	 * Stores a list of recognized image extensions
	 * @var array
	 */
	private $imageExtensions = array('jpg','jpeg','png','gif');
	
	public function init($args = array()) {
		
	}
	
	public function remove($file) {
		if (!is_file($file)) {
			$this->logL(constant('LOG_USER_ERROR'), 'E_REMOVE_FILE_NOT_EXISTS');
			return false;
		}
		$info = pathinfo($file);
		// If file is image, mark it as such and check if it's a readable image. If not, throw error.
		if (in_array($info['extension'], $this->imageExtensions)) {
			return $this->tool('image')->remove($file);
		} else {
			if (unlink($file)) return true;
			else {
				$this->logL(constant('LOG_USER_ERROR'), 'E_REMOVE_FILE_FAILED');
				return false;
			}
		}
	}
	
	public function isImage($file, $pathinfo = false) {
		if (!$pathinfo) $pathinfo = pathinfo($file);
		if (
			in_array($pathinfo['extension'], $this->imageExtensions) and
			$imagesize = getimagesize($location) and
			$imagesize[0] > 0
		) return $imagesize;
		return false;
	}
	
	public function send($file) {
		// send the right headers
		$mime = mime_content_type($file);
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $mime);
		header('Content-Disposition: attachment; filename=' . basename($file));
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header("Content-Length: " . filesize($file));
		
		// dump the file and stop the script
		readfile($file);
		exit;
	}
}
?>