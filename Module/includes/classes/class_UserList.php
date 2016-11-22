<?php
class UserList {
	
	
	public function getAdminUserSelect($user_id) {
		$rs = execute("SELECT user_id, display_name FROM admin_users ORDER BY display_name");
	
		$content = "<select name=\"user_id\">
		<option value=\"\">All users</option>";

		while ($row = get_row($rs))
		{
		$content .= "<option value=\"{$row['user_id']}\"".($user_id == $row['user_id'] ? ' selected' : '').">{$row['display_name']}</option>";
		}

		$content .= "</select>";
		return $content;
	}

	public static function getUserTally() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT COUNT(*) AS tally
							FROM public_users
							WHERE status & 1
							AND NOT status & 2
							AND (
								verified = 1 OR verification_sent = 0
							)');
		$row = $rs->getRow();
		return $row['tally'];
	}

	public static function getUserOnlineTally() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT COUNT(*) AS online_users
							FROM session_data
							WHERE expire <= NOW()');
		$row = $rs->getRow();
		return $row['online_users'];
	}

	public static function getLatest() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT user_id
							FROM public_users
							WHERE status & 1
							AND NOT status & 2
							AND (
								verified = 1 OR verification_sent = 0
							)
							ORDER BY ts_registered
							DESC LIMIT 1');
		$row = $rs->getRow();
		return new User($row['user_id']);
	}
	
	public function searchAdmin($pager, $ss = '') {
		global $time;
		$ss = ltrim(str_replace('*', '%', $ss));
		$query = "SELECT *
								FROM public_users u
								LEFT JOIN gk4_areas a ON (u.area_id = a.area_id)
				";
		if ($ss) $query .= " WHERE (
										user_id LIKE '$ss%'
										OR family_name LIKE '$ss%'
										OR given_name LIKE '$ss%'
										OR nickname LIKE '$ss%'
										OR password LIKE '$ss%'
										OR email LIKE '$ss%'
										OR u.user_id = '$ss'
										OR ip LIKE '$ss%'
							) ";
		$query .= " ORDER BY user_id DESC";
		$rs = $pager->execute($query);
		if ($rs->getNum()) {
			$content .= '<table class="main">';
			$header = array('ID',
							'Nickname',
							'Password',
							'First Name',
							'Family Name',
							'Email',
							'Area',
							'IP',
							'Registered',
							'Status',
							'verified',
							'');
			$content .= ContentCleaner::wrapArrayInTh($header);
			while ($row = $rs->getRow()) {
				$data = array(	"<a href=\"/en/users/profile/" . $row['user_id'] . "/\" target=\"_blank\">" . $row['user_id'] . "</a>",
					$row['nickname'],
					$row['password'],
					$row['given_name'],
					$row['family_name'],
					$row['email'],
					str_replace(' ', '&nbsp;', $row['area_en']),
					$row['ip'],
					str_replace(' ', '&nbsp;', $row['ts_registered']),
					$row['status'] & 1 ? ($row['verified'] == 1 || $row['verification_sent'] == 0 ? 'Live' : 'Email') : 'Banned',
					$row['verified'] ? 'yes' : 'no',
					'<a href="form_user.php?user_id='.$row['user_id'].'">Edit</a>');
				$content .= ContentCleaner::wrapArrayInTr($data, $row['status'] & 1 && ($row['verified'] == 1 || $row['verification_sent'] == 0) ? '' : 'fadeout');
			}
			$content .= '</table>';
		}
		
		return $content;
	}
}
?>
