<?php
class LinksController
{
	function index() {
		global $user;
	
		$p = new Page;
		$p->setTag('page_title', "Useful Links");
		
		$folder_id = 1;
	
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM links_folders
							WHERE parent_id=$folder_id
							ORDER BY position");
	
		while ($row = $rs->getRow()) {
			$body .= "<h1>{$row['folder']}</h1>";
			$lf = new LinksFolder;
			$lf->setData($row);
			$body .= $lf->display_contents();
			$body .= "<br /><br />";
		}
		
		$p->setTag('main', $body);
		$p->output();
	}
}
?>