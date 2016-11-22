<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

set_time_limit(0);

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings_photos');

	if (file_exists(IMAGE_STORE_DROP.'meta.txt'))
	{
	$body .= 'Reading meta data...<br />';
	$meta = file(IMAGE_STORE_DROP.'meta.txt');
	unlink(IMAGE_STORE_DROP.'meta.txt');
	$body .= count($meta).' records read<br />';
	
		foreach ($meta as $metam)
		{
		$meta_pieces = explode(',', $metam);
		// $meta_data[$filename] = $ts
		$meta_data[$meta_pieces[0]] = $meta_pieces[1];
		}
		
	$body .= count($meta).' records read<br />';
	
	$body .= 'Reading photos...<br />';
	
		if ($handle = opendir(IMAGE_STORE_DROP))
		{
		chdir(IMAGE_STORE_DROP);
		
			while (false !== ($file = readdir($handle)))
			{
				if (!is_dir($file))
				{
				$body .= "Reading photo $file<br />";
				$photo = new Photo;
				$photo->saveFromFileSystem(IMAGE_STORE_DROP.$file, $meta_data[$file]);
				$body .= "Photo $file saved as photo_id ".$photo->getPhotoID()."<br />";
				$body .= "Seeking location for $file<br />";
				$latlon = $photo->seekGPS();
				$body .= "$file saved to $latlon<br />";
				$body .= "Deleting $file<br />";
				unlink($file);
				}
			}
		
		closedir($handle);
		}
	}

$pap->setTag('main', $body);
$pap->output();
?>