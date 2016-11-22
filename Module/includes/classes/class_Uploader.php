<?php

class UploaderTools {
	/**
	 * Stores a more sensible version of the $_FILES array, in case of recursive groups in the input name
	 * @var array
	 */
	public $files = array();
	// All the messages (errors and succeeds)
	var $messages = array();
	/**
	 * An array of the successful uploads, conforming the $_FILES structure.
	 * @var array
	 */
	public $successful = array();
	/**
	 * An array of the failed uploads, conforming the $_FILES structure.
	 * @var array
	 */
	public $failed = array();
	/**
	 * An array of the unzipped uploads, conforming the $_FILES structure.
	 * @var array
	 */
	public $unzipped = array();
	/**
	 * An array of the empty uploads, conforming the $_FILES structure.
	 * @var array
	 */
	public $empty = array();
	/**
	 * All uploads wil be copied to this folder.
	 * 
	 * @see UploaderTools::setUploadFolder()
	 * 
	 * @var string
	 */
	private $folder = false;
	/**
	 * Stores a list of allowed file extensions
	 * @var array
	 */
	private $extensions = array('jpg','jpeg','png','gif');
	/**
	 * Stores a list of allowed mimetypes.
	 * @var array
	 */
	private $mimes = array('image/png','image/jpg','image/jpeg','image/pjpeg','image/gif');
	/**
	 * Flag that denotes whether we should unzip archives (only zip supported atm)
	 * @var boolean
	 */
	public $extractArchives = false;
	
	public function __construct($args) {
		if (is_array($_FILES)) $this->files = $this->rebuildFilesArray($_FILES);
	}
	
	/**
	 * In case of input types like <input type=file name=group1[group2][property]> we can rebuild the $_FILES array
	 * which corresponds to the way the $_GET and $_POST are build.
	 * 
	 * @param array $files
	 * @return array
	 */
	private function sanitiseFiles(array $files) {
		$result = array();
		foreach($files as $name => $file) {
			if (is_array($file['name'])) {
				$result[$name] = $this->sanitiseFiles(array_transpose($file));
			} else {
				$result[$name] = $file;
			}
		}
		return $result;
	}
	
	/**
	 * In case of input types like <input type=file name=group1[group2][property]> we can rebuild the $_FILES array
	 * so it will like like: $files = array('group1/group2/property' => $file)
	 * 
	 * @param array $files
	 * @return array
	 */
	private function rebuildFilesArray(array $files, $path = '') {
		$result = array();
		if ($path) $path .= '/';
		foreach($files as $name => $file) {
			if (is_array($file['name'])) {
				$result = array_merge($result, $this->rebuildFilesArray(array_transpose($file), $path . $name));
			} else {
				$file['field'] = $name;
				$result[$path . $name] = $file;
			}
		}
		return $result;
	}
	
	/**
	 * Returns whether a file upload exists (empty values are ignored)
	 * @param string $fieldname
	 * @return boolean
	 */
	public function exists($fieldname) {
		return array_key_exists($fieldname, $this->files) && $this->files[$fieldname]['name'];
	}
	
	/**
	 * Returns what the current maximum upload size is in a formatted string
	 * @return string
	 */
	public function getMaxUploadSize() {
		$post_max_size = return_bytes(ini_get('post_max_size'));
		$upload_max_filesize = return_bytes(ini_get('upload_max_filesize'));
		return min($post_max_size, $upload_max_filesize);
	}

	/**
	 * Sets the valid extensions for the current upload session.
	 * @param string|array $extensions When a string is given, the comma is used as delimiter
	 */
	public function setExtensions($extensions = false) {
		if (is_string($extensions)) {
			$extensions = array_map('trim', explode(',', $extensions));
		}
		if (is_array($extensions)) $this->extensions = array_map('strtolower', $extensions);
	}
	
	/**
	 * Sets the valid mime types for the current upload session.
	 * @param string|array $mimes When a string is given, the comma is used as delimiter
	 */
	public function setMimetypes($mimes = false) {
		if (is_string($mimes)) {
			$mimes = array_map('trim', explode(',', $mimes));
		}
		if (is_array($mimes)) $this->mimes = array_map('strtolower', $mimes);
	}

	/**
	 * Sets the current upload folder, when valid and writable
	 * 
	 * @param string $uploadFolder
	 * @return boolean
	 * 
	 * @see FolderTools::create()
	 */
	public function setUploadFolder($uploadFolder) {
		if ($this->tool('folder')->create($uploadFolder)) {
			$this->folder = $uploadFolder;
			return true;
		}
		return false;
	}

	public function captureAllUploads(array $accept = NULL) {
		if (!$this->folder) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_UPLOAD_FAILED_NO_UPLOAD_FOLDER');
			return false;
		}
		if (!$this->files) {
			$this->logL(constant('LOG_SYSTEM_WARNING'), 'E_UPLOAD_FAILED_NO_UPLOADS');
			return false;
		}
		
		foreach ($this->files as $fieldname => $file) {
			if ($accept && !in_array($fieldname, $accept)) {
				continue;
			}
			$info = pathinfo($file['name']);
			$file['name'] = str_replace(array('/', "'"), '', $file['name']);
			$file['fieldname'] = $fieldname;
			
			if ($this->extractArchives && strtolower($info['extension']) == "zip") {
				$this->unzipfile($file);
			} else {
				$this->captureUpload($fieldname, $file);
			}
		}
		if (count($this->files) == 0) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_UPLOAD_FAILED_NO_FILES_SELECTED');
		}
		return (count($this->successful));
	}

	private function unzipfile($file){
		include_once($GLOBALS['pb_paths']['tools'] . 'pclzip/pclzip.lib.php');
		$archive = new PclZip($file['tmp_name']);
		if (($list = $archive->listContent()) == 0) {
			die("Error : ".$archive->errorInfo(true));
		}
		$files = array();
		for ($i=0; $i<sizeof($list); $i++) {
			for(reset($list[$i]); $key = key($list[$i]); next($list[$i])) {
				echo "File $i / [$key] = ".$list[$i][$key]."<br>";
			}
			$files[] = array('name' => $list[$i]['filename'], 'size' => $list[$i]['size']);
			echo "<br>";
		}
		$zipFolder = $this->folder . 'zip/';
		if(!is_dir($zipFolder)){
			mkdir($zipFolder);
		}

		foreach($files as $file) {
			if (@$archive->extract(PCLZIP_OPT_BY_NAME, $file['name'], PCLZIP_OPT_PATH, $zipFolder) == TRUE) {
				echo $file['name'] . " extracted<br>";
				if ($this->addFile($zipFolder.$file['name'], $file)) $this->unzipped[] = $file;
				unlink($zipFolder.$file['name']);
			}
		}
	}

	public function captureUpload($fieldname, $file = false) {
		if ($fieldname && !$file) {
			$file = array_key_exists($fieldname, $this->files) ? $this->files[$fieldname] : false;
		}
		
		if (!$file || !is_array($file)) {
			$this->empty[] = $file;
			return false;
		}
		
		$file['fieldname'] = $fieldname;

		if (!request($file['name'])) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_UPLOAD_FAILED_NO_FILENAME');
			$this->failed[] = $file;
			return false;
		}
		
		$error = request($file['error']);
		//make sure something is there
		if ($error > 0) {
			if ($error == 4) $this->logL(constant('LOG_USER_ERROR'), 'E_UPLOAD_FAILED_NO_FILENAME');
			elseif ($error > 0 and $error < 3) $this->log(constant('LOG_USER_ERROR'), $this->lang('E_UPLOAD_FAILED_FILESIZE') . ': <strong>'  . $file['name'] . '</strong>');
			else $this->log(constant('LOG_USER_ERROR'), $this->lang('E_UPLOAD_FAILED') . ': <strong>'  . $file['name'] . '</strong>');
			$this->empty[] = $file;
			return false;
		}
		
		return $this->addFile($file['tmp_name'], $file);
	}

	public function addFile($location, $file = array()){
		if (!$this->folder) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_UPLOAD_FAILED_NO_UPLOAD_FOLDER');
			return false;
		}

		if (!isset($file['name'])) {
			$pathinfo = pathinfo($location);
			$file['name'] = $pathinfo['base'];
		} else $pathinfo = pathinfo($file['name']);
		
		$file['target'] = $this->folder . $file['name'];
		$file['source'] = $location;
		
		$extension = $pathinfo["extension"];

		//normalize the file variable
		if (!isset($file['type']))      $file['type']      = array_get(explode(';', mime_content_type($location)), 0);
		if (!isset($file['size']))      $file['size']      = '';
		if (!isset($file['tmp_name']))  $file['tmp_name']  = '';
		
		if (
			(is_array(request(($this->extensions))) && !in_array(strtolower($extension), $this->extensions)) ||
			(is_array(request(($this->mimes))) && !in_array($file['type'], $this->mimes))
		) {
			

			if (!in_array(strtolower($extension), $this->extensions)) {
				$this->log(constant('LOG_USER_ERROR'), $this->lang('E_FILETYPE_NOT_ALLOWED') . ': <strong>' . $file['name'] . '</strong>');
				$this->failed[] = $file;
				return false;
			}
		}

		$file['name'] = preg_replace(
			 '/[^a-zA-Z0-9\.\$\%\'\`\-\@\{\}\~\!\#\(\)\&\_\^]/'
			 ,'',str_replace(array(' ','%20'),'_',$file['name']));

		//normalize destDir
		if (strlen($this->folder) > 0 && $this->folder[strlen($this->folder) - 1] != "/") {
			$this->folder = $this->folder.'/';
		}
	
		$i = 0;
		//if the filename already exists, append _copy_x (with extension)
		if(strpos($file['name'],'.') !== false){
			$bits = explode('.',$file['name']);
			$ext = array_pop($bits);
			while(file_exists($this->folder.implode('.', $bits).($i?'_'.$i:'').'.'.$ext)){
				++$i;
				$file['name'] = implode('.',$bits).($i?'_'.$i:'').'.'.$ext;
			}
		}
		
		$file['target'] = $this->folder . $file['name'];

		if(!copy($location, $file['target'])) {
			$this->logL(constant('LOG_USER_ERROR'), 'E_UPLOAD_FAILED_FOLDER_NOT_WRITABLE');
			$this->failed[] = $file;
			return false;
		} else {
		 	$this->successful[] = $file;
		 	return true;
		}
	}
}
?>