<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');

set_time_limit(0);
$db = new DatabaseQuery;

$db->execute("TRUNCATE photos");
$db->execute("TRUNCATE photos_photographers");
$db->execute("TRUNCATE photos_tags");
$db->execute("TRUNCATE photos_albums");

$photographers = array('Henrietta',
					   'Idgie',
					   'John',
					   'Keith',
					   'Lennie',
					   'Mark',
					   'Naomi',
					   'Oliver',
					   'Polly');

foreach ($photographers as $photographer) {
	$db->execute("	INSERT INTO photos_photographers (photographer)
					VALUES ('$photographer')");
}

$albums = array('Massive Party at Lake Constance',
				'Hike to Everest',
				'Skateboarding in Tokyo',
				'Welcome to the Jungle',
				'Alligator Escape',
				'Bike Bites Back',
				'Trolley in River',
				'Forest Fire',
				'Lollipop Licking Ladies');

foreach ($albums as $album) {
	$db->execute("	INSERT INTO photos_albums (album, site_id)
					VALUES ('$album', 1)");
}

for ($i = 5; $i < 2000; $i++) {
	echo $i."\n";
	$db->execute("INSERT INTO photos (photo_id, site_id, album_id, photographer_id, orientation, ts)
				 VALUES ($i, 1, ".rand(0, count($albums)-1).", ".rand(0, count($photographers)-1).", 'L', NOW())");
	$num_tags = rand(3, 8);
	for ($j = 0; $j < $num_tags; $j++) {
		$db->execute("INSERT INTO photos_tags (photo_id, tag)
					 VALUES ($i, '".make_word()."')");
	}

	$photo_id = rand(1, 4);
	copy(GALLERY_PHOTO_STORE_FILEPATH.$photo_id.'.jpg', GALLERY_PHOTO_STORE_FILEPATH.'/'.$i.'.jpg');
	copy(GALLERY_PHOTO_STORE_FILEPATH.'thumbnail/'.$photo_id.'.jpg', GALLERY_PHOTO_STORE_FILEPATH.'/thumbnail/'.$i.'.jpg');
}

function make_word() {
	$letters = range('a', 'z');
	$length = rand(4, 10);

	for ($i = 0; $i <= $length; $i++) {
		$word .= $letters[rand(0, 25)];
	}

	return $word;
}
?>