<?php
class AdminUserList {

	public function display() {
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT *
								FROM admin_users
								ORDER BY family_name, given_name");

		$content = "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>User ID</b></td>
		<td><b>Given Name</b></td>
		<td><b>Family Name</b></td>
		<td><b>Email</b></td>
		<td><b>Live</b></td>
		<td></td>
		</tr>";

		while ($row = $rs->getRow()) {
			$content .= "<tr>
			<td>{$row['user_id']}</td>
			<td>{$row['given_name']}</td>
			<td>{$row['family_name']}</td>
			<td>{$row['username']}</td>
			<td>{$row['live']}</td>
			<td><a href=\"form_user.php?user_id={$row['user_id']}\">Edit</a></td>
			</tr>";
		}

		$content .= "</table>";
		return $content;
	}

	public function displayPublic() {
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT *
								FROM admin_users u, admin_users2sites u2s
								WHERE u2s.user_id = u.user_id
								AND u2s.bio != ''
								AND live = 1
								ORDER BY display_name");

		while ($row = $rs->getRow()) {
			$admin_user = new AdminUser;
			$admin_user->setData($row);
			$users[] = $admin_user->displayPublic();
		}

		return HTMLHelper::wrapArrayInUl($users, 'team_list', false, 'vcard');
	}
	
	public function getFormer() {
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT *
								FROM admin_users
								WHERE live != 1
								ORDER BY display_name");

		while ($row = $rs->getRow()) {
			$admin_user = new AdminUser;
			$admin_user->setData($row);
			$users[] = $admin_user->displayFormer();
		}

		return HTMLHelper::wrapArrayInUl($users);
	}

	function displayCheckboxList($user_ids_checked) {
		$db = new DatabaseQuery;
		$user_ids_checked = !is_array($user_ids_checked) ? array() : $user_ids_checked;
		$content = "<table cellpadding=\"0\" cellspacing=\"0\">";
		$rs = $db->execute("	SELECT user_id, CONCAT(given_name, ' ', family_name) AS name
								FROM admin_users
								ORDER BY family_name, given_name");

		while ($row = $rs->getRow()) {
			$content .= "<tr>
			<td><input type=\"checkbox\" name=\"user_ids[]\" value=\"{$row['user_id']}\"".(in_array($row['user_id'], $user_ids_checked) ? ' checked' : '')."></td>
			<td>{$row['name']}</td>
			</tr>";
		}

		$content .= "</table>";
		return $content;
	}
	
	public function getArray() {
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT user_id, CONCAT(given_name, ' ', family_name) AS name
								FROM admin_users
								ORDER BY family_name, given_name");
		while ($row = $rs->getRow())
			$users[$row['user_id']] = $row['name'];
		return $users;
	}

	function displaySelect($user_id_selected) {

		$content = "<select name=\"user_id\">";

		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT user_id, CONCAT(given_name, ' ', family_name) AS name
							FROM admin_users
							WHERE live=1
							ORDER BY family_name, given_name");

		while ($row = $rs->getRow()) {
			$content .= "<option value=\"{$row['user_id']}\"".($row['user_id'] == $user_id_selected ? ' selected' : '').">{$row['name']}</option>";
		}

		$content .= "</select>";
		return $content;
	}
}
?>