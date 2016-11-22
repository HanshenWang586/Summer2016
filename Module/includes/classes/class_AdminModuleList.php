<?php
class AdminModuleList {

	public function display() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM admin_modules');

		$content .= "<table cellspacing=\"1\" class=\"gen_table\">
		<tr valign=\"top\">
		<td><b>Module ID</b></td>
		<td><b>Module Key</b></td>
		<td><b>Module</b></td>
		<td><b>URL</b></td>
		<td><b>Open</b></td>
		<td></td>
		</tr>";

		while ($row = $rs->getRow()) {
			$content .= "<tr valign=\"top\">
			<td>{$row['module_id']}</td>
			<td>{$row['module_key']}</td>
			<td>{$row['menu_text']}</td>
			<td>{$row['menu_link']}</td>
			<td>{$row['open_to_all']}</td>
			<td><a href=\"form_module.php?module_id={$row['module_id']}\">Edit</a></td>
			</tr>";
		}

		$content .= '</table>';
		return $content;
	}
	
	public static function getArray() {
		$modules = array();
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM admin_modules
							WHERE open_to_all = 0
							ORDER BY menu_text');
		
		while ($row = $rs->getRow())
			$modules[$row['module_id']] = $row['menu_text'];
		
		return $modules;
	}
}
?>
