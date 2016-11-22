<?
class FolderTools extends CMS_Class {
	public function init($args) {
		
	}
	
	public function create($path) {
		if (!is_dir($path)) {
			$old = umask(0);
			@mkdir($path, 0755, true);
			umask($old);
			if (!is_dir($path)) {
				$this->log(constant('LOG_SYSTEM_WARNING'), $this->lang('E_FOLDER_NOT_EXISTS') . ': ' . $path);
				return false;
			}
			if (!is_writable($path)) {
				$this->log(constant('LOG_SYSTEM_WARNING'), $this->lang('E_FOLDER_NOT_WRITABLE') . ': ' . $path);
				return false;
			}
		}
		return true;
	}
	
	function delete($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir.DIRECTORY_SEPARATOR.$object) == "dir") $this->delete($dir.DIRECTORY_SEPARATOR.$object); else unlink($dir.DIRECTORY_SEPARATOR.$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	} 
}
?>
