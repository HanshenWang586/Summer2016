<?php
class ExifReader {

	private $exif;


	public function setPath($path) {
		$this->path = $path;
	}

	public function getTagByName($name) {
		
		if (!isset($this->exif))
			$this->readExif();
		
		if ($this->exif !== false)
			return $this->exif[$name];
		else
			return false;
	}

	private function readExif() {
	
		if (!file_exists($this->path)) {
			die('file not found');
		}
		else {
			$this->exif = @exif_read_data($this->path);
		}
	}

	public function getDecimalLatitude() {
		
		$dms = $this->getTagByName('GPSLatitude');
		if ($dms) {
			$degrees = eval('return '.$dms[0].';');
			$minutes = eval('return '.$dms[1].';');
			$seconds = eval('return '.$dms[2].';');
			
			$dl = $degrees + $minutes/60 + $seconds/3600;
			
			if (strtolower($this->getTagByName('GPSLatitudeRef')) == 's') {
				return -1*$dl;
			}
			else {
				return $dl;
			}
		}
		else
			return 0;
	}

	public function getDecimalLongitude() {
		
		$dms = $this->getTagByName('GPSLongitude');
		if ($dms) {
			$degrees = eval('return '.$dms[0].';');
			$minutes = eval('return '.$dms[1].';');
			$seconds = eval('return '.$dms[2].';');
			
			$dl = $degrees + $minutes/60 + $seconds/3600;
			
			if (strtolower($this->getTagByName('GPSLongitudeRef')) == 'w') {
				return -1*$dl;
			}
			else {
				return $dl;
			}
		}
		else
			return 0;
	}

	public function getDecimalAltitude() {
		
		$alt = $this->getTagByName('GPSAltitude');
		return eval('return '.$alt.';') + 0;
	}

	public function getTimeShot() {
		
		$ts = $this->getTagByName('DateTimeOriginal');
		return preg_replace(':', '-', $ts, 2);
	}

	public function getKMLCoordinates() {
		return "<coordinates>".$this->getDecimalLongitude().",".$this->getDecimalLatitude().",".$this->getDecimalAltitude()."</coordinates>";
	}
}
?>