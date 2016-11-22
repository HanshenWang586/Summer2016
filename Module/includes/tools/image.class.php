<?
class ImageTools extends CMS_Class {
	public function init($args) {
		
	}
	
	public function resize($file, $width = NULL, $height = NULL, $replace = false, $crop = false) {
		$width = (int) $width;
		$height = (int) $height;
		if (!$width && !$height) {
			$this->logL(constant('LOG_SYSTEM_WARNING'), 'E_RESIZE_NO_WIDTH_OR_HEIGHT');
			return false;
		}
		
		$fileInfo = pathinfo($file);
		$path = $fileInfo['dirname'] . DIRECTORY_SEPARATOR;
		if (!file_exists($file)) {
			$this->logL(constant('LOG_SYSTEM_WARNING'), 'E_RESIZE_FILE_NOT_FOUND');
			return false;
		}
		
		$image_size = getimagesize($file);
		if (!$crop) {
			$width = $width ? min($width, $image_size[0]) : $image_size[0];
			$height = $height ? min($height, $image_size[1]) : $image_size[1];
		}
		if ($image_size[0] > $width || $image_size[1] > $height) {
			if (!$replace) {
				$targetFolder = sprintf('%s%s%s', $path, 'cache', DIRECTORY_SEPARATOR);
				$target = sprintf("%s%s_%dx%d%s.%s", $targetFolder, $fileInfo['filename'], $width, $height, $crop ? 'c' : '', $fileInfo['extension']);
			} else {
				$targetFolder = $path;
				$target = $file;
			}
			if (!$this->tool('folder')->create($targetFolder)) {
				return false;
			}
			// If the thumbnail does not exist, create it! Otherwise, continue
			if ($replace || !file_exists($target)) {
				$quality = ifElse($options['quality'], 90);
				$extra = $crop ? sprintf('^ -gravity center -extent %dx%d', $width, $height) : '';
				$command = sprintf('convert -strip -interlace Plane %s -quality %d -filter lanczos -resize %dx%d%s %s 2>&1', escapeshellarg($file), $quality, $width, $height, $extra, escapeshellarg($target));
				$result = exec($command, $output);
				if (file_exists($target)) return $target;
			} else return $target;
		}
		return $file;
	}
	
	public function remove($images, $imagesPath = false) {
		if (!is_array($images)) $images = array($images);
		$this->clearCache($images, $imagesPath);
		$unlinked = 0;
		if (!$imagesPath) {
			foreach($images as $image) {
				if (file_exists($image)) {
					$info = pathinfo($image);
					$unlnked += $this->remove($info['basename'], $info['dirname']);
				}
			}
			return $unlinked;
		}
		foreach($images as $image) {
			if (unlink($imagesPath . '/' . $image)) $unlinked++;
		}
		return $unlinked;
	}
	
	public function clearCache($images, $imagesPath = false) {
		if (!is_array($images)) $images = array($images);
		$unlinked = 0;
		if (!$imagesPath) {
			foreach($images as $image) {
				if (file_exists($image)) {
					$info = pathinfo($image);
					$unlnked += $this->clearCache($info['basename'], $info['dirname']);
				}
			}
			return $unlinked;
		}
		$cacheDir = $imagesPath . "/cache/";
		foreach($images as $index => $image) {
			$fileInfo = pathinfo($image);
			$images[$index] = substr($fileInfo['basename'], 0, strpos($fileInfo['basename'], $fileInfo['extension']) - 1) . "_";
		}
		if ($handle = opendir($cacheDir)) {
			while (false !== ($filename = readdir($handle))) {
				if (strpos_r($filename,  $images)) {
					if (unlink($cacheDir . $filename)) $unlinked++;
				}
			}
			closedir($handle);
		}
		$this->message .= $unlinked . " thumbnails have been deleted from disk.<br>";
		return $unlinked;
	}
	
	public function show($file, $width = false, $height = false, $options = array()) {
		if (!file_exists($file)) HTTP::throw404();
		
		$info = pathinfo($file);
		$name = $info['basename'];
		
		if (!isset($_GET['noCache']) && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) <= filemtime($file))) {
			// Client's cache IS current, so we just respond '304 Not Modified'.
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 304);
			exit();
		} else {
			if (($width || $height) && $resize = $this->resize($file, $width, $height)) $file = $resize; 
			$mimetype = array_get(explode(';', mime_content_type($file)), 0);
			// Image not cached or cache outdated, we respond '200 OK' and output the image.
			
			header("Pragma: cache");
			header('Cache-Control: max-age=2592000');
			header("Content-Type: " . $mimetype);
			header(sprintf("Content-Disposition: inline; filename=\"%s\"", $name));
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)).' GMT');
			header('Content-Length: ' . filesize($file));
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 30)) . " GMT");
			readfile($file);
			
			exit();
		}
	}
}
?>
