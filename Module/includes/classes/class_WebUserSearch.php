<?php
class WebUserSearch
{
	function set_ss($ss)
	{
	$this->show_form = true;

		if ($ss != '')
		{
		$this->ss = $ss;
		$this->ss_sql = str_replace('*', '%', $ss);
		$this->set_sql("	SELECT u.user_id, site_name, nickname, password, given_name, family_name, email, area_en, ip, ts_registered, status
							FROM public_users u, gk4_areas a, sites s
							WHERE u.area_id = a.area_id
							AND (
									user_id LIKE '$this->ss_sql%'
									OR family_name LIKE '$this->ss_sql%'
									OR given_name LIKE '$this->ss_sql%'
									OR nickname LIKE '$this->ss_sql%'
									OR password LIKE '$this->ss_sql%'
									OR email LIKE '$this->ss_sql%'
									OR u.user_id = '$this->ss_sql'
									OR ip LIKE '$this->ss_sql%'
								)
							ORDER BY ts_registered DESC, family_name, given_name");
		}
	}

	function set_sql($sql)
	{
	$this->sql = $sql;
	}

	function display_form()
	{
	$content = "<form action=\"index.php\" method=\"get\">
	<input name=\"ss\" value=\"$this->ss\"> <input type=\"submit\" value=\"Search\">
	</form><br />
	<a href=\"index.php?ss=*\">Display all</a><br /><br />";
	return $content;
	}

	function display(&$pager)
	{
		if ($this->show_form)
		{
		$content = $this->display_form();
		}

	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT COUNT(*) AS total FROM public_users");
	$row = $rs->getRow();
	$total = $row['total'];

	if ($this->sql!='')
	{
	$pager->setTotal($total);
	$rs = $pager->setSQL($this->sql);

	$content .= "[database total = $total]<br /><br />";

		while ($row = $rs->getRow())
		{
			if (!is_array($header))
			{
			$header = array_keys($row);

			$content .= "<table cellspacing=\"1\" class=\"gen_table\">
						<tr valign=\"top\">
						<td><b>";

			$content .= implode("</b></td><td><b>", $header);
			$content .= "</b></td><td></td></tr>";
			}

		$content .= "<tr valign=\"top\">";

			foreach ($header as $key)
			{
			$content .= "<td>{$row[$key]}</td>";
			}

		if ($row['status'] & 1)
			$ban = "<a href=\"ban_user.php?user_id={$row['user_id']}\" onClick=\"return conf_del()\">Ban</a>";
		else
			$ban = "<font color=\"#ff0000\"><b>BANNED</b></font>";

		$content .= "<td>$ban</td></tr>";
		}
	$content .= "</table>";
	}
	return $content;
	}
}
?>