<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

//header("Content-type: text/plain");

if (strpos($_GET['section_tag'], '.')) {
	$section_tag_bits = explode('.', $_GET['section_tag']);
	
	//print_r($section_tag_bits);
	
	$last_digit = $section_tag_bits[count($section_tag_bits)-1];
	
	//echo $last_digit;
	
	$section_tag_bits = array_slice($section_tag_bits, 0, count($section_tag_bits)-1);
	
	//print_r($section_tag_bits);
	
	$current_tag = implode('.', $section_tag_bits);
	
	//echo $current_tag;
	
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *, SUBSTRING(section_tag, LENGTH(section_tag) + 2 - LOCATE('.', REVERSE(section_tag))) AS last_digit
						FROM book_sections
						WHERE section_tag REGEXP '^{$current_tag}\.[[:digit:]]+$'
						AND live = 1
						ORDER BY SUBSTRING(section_tag, LENGTH(section_tag) + 2 - LOCATE('.', REVERSE(section_tag))) + 0 ASC");
	
	while ($row = $rs->getRow()) {
		//print_r($row);
	
		if ($row['last_digit'] > $last_digit) {
			$db->execute("	UPDATE book_sections
							SET section_tag='{$current_tag}.".($row['last_digit'] + 1)."'
							WHERE section_id={$row['section_id']}");
		}
	}
	
	$db->execute("	INSERT INTO book_sections (section_tag)
					VALUES ('{$current_tag}.".($last_digit + 1)."')");
}
else {
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM book_sections
						WHERE section_tag NOT LIKE '%.%'
						AND live = 1
						ORDER BY section_tag + 0 ASC");
						
	while ($row = $rs->getRow()) {
		if ($row['section_tag'] > $_GET['section_tag']) {
			$db->execute("	UPDATE book_sections
							SET section_tag='".($row['section_tag'] + 1)."'
							WHERE section_id={$row['section_id']}");
		}
	}

	$db->execute("	INSERT INTO book_sections (section_tag)
					VALUES ('".($_GET['section_tag'] + 1)."')");
}


HTTP::redirect('index.php');
?>