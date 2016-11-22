<?php

class QR {
	public function generate($url) {
		global $model;
		$dir = $model->paths['root'] . 'images/qrcodes/';
		if ($model->tool('folder')->create($dir)) {
			include_once("phpqrcode/qrlib.php");
			$url .= '?QR';
			$file = md5($url) . '.png';		
			$path = $dir . $file;
			if (!file_exists($path)) QRcode::png($url, $path, 'Q', 4, 2);
			return str_replace($model->paths['root'], $model->urls['root'] . '/', $path);
		}
		return false;
	}
}

?>