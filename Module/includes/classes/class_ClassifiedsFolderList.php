<?php
class ClassifiedsFolderList {

	public static function getFolders() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM classifieds_folders
							ORDER BY parent_id ASC, folder_en');

		while ($row = $rs->getRow()) {
			$cf = new ClassifiedsFolder;
			$cf->setData($row);

			if (!$cf->hasChildren())
				$folders[$row['folder_id']] = strip_tags($cf->getPath());
		}

		asort($folders);
		return $folders;
	}
}
?>
