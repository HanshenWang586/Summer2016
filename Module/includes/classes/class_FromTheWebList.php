<?php
class FromTheWebList {

	public function getItems() {
		$languages = array(1 => 'English', 2 => 'Chinese');
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   FROM fromtheweb
						   WHERE ts > DATE_SUB(ts, INTERVAL 10 DAY)
						   ORDER BY ts DESC
						   LIMIT 5");

		while ($row = $rs->getRow()) {
			$items[] = "<a href=\"{$row['url']}\" target=\"_blank\" onmouseover=\"showFTWToolTip({$row['ftw_id']})\" onmouseout=\"hideFTWToolTip({$row['ftw_id']})\">{$row['title']}</a>".($row['language_id'] == 2 ? ' (Chinese)' : '')."
			<div id=\"ftw_{$row['ftw_id']}\" class=\"ftw\">".nl2br($row['body'])."</div>";
		}

		return HTMLHelper::wrapArrayInUl($items);
	}

	public function hasItems() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   FROM fromtheweb
						   WHERE ts > DATE_SUB(ts, INTERVAL 10 DAY)");
		return $rs->getNum();
	}

	public function displayAdmin(&$pager) {
		global $admin_user;
		$rs = $pager->setSQL('SELECT *
							 FROM fromtheweb
							 ORDER BY ts DESC');

		$content .= "<table cellspacing=\"1\" class=\"gen_table\">";

		while ($row = $rs->getRow()) {
			$content .= "<tr valign=\"top\">
						<td width=\"500\"><a href=\"{$row['url']}\"><b>{$row['title']}</b></a>".ContentCleaner::PWrap($row['body'])."</td>
						<td><a href=\"form_ftw.php?ftw_id={$row['ftw_id']}\">Edit</a></td>
						<td><a href=\"delete.php?ftw_id={$row['ftw_id']}\" onclick=\"return conf_del()\">Delete</a></td>
						</tr>";
		}

		$content .= '</table>';
		return $content;
	}
	
	public function displayPublic(&$pager) {
		$rs = $pager->setSQL('SELECT *
							 FROM fromtheweb
							 ORDER BY ts DESC');

		while ($row = $rs->getRow()) {
			$ftw = new FromTheWeb;
			$ftw->setData($row);
			$items[] = $ftw->getPublic();
		}
		
		return HTMLHelper::wrapArrayInUl($items);
	}
}
?>