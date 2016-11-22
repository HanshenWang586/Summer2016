<?php

class SettingsModel {
	var $table;
	
	function SettingsModel() {
		$this->table = getTableName('preferences');
	}
	
	// Get the settings for the module specified by the module name.
	function getSettings($module = false, $user = false, $db_settings = false) {
		if (!$module) return false;
		if (!$user) $user = "system";
		
		$link = connect($db_settings);
		$settings = array_transpose(db_select($this->table, $link, array('module' => $module, 'user' => $user), 'name, value'), 'name', 'value');
		disconnect($link);
		return $settings;
	}
	
	function updateSettings($settings, $module = false, $user = false, $db_settings = false) {
		if (!$module) return false;
		ifNot($user,"system");
		$query = sprintf("REPLACE INTO %s (`module`, `name`, `value`, `user`) VALUES ", $this->table);
		$i = 0;
		$link = connect($db_settings);
		foreach ($settings as $key => $value) {
			if ($i > 0) {
				$query .= ", ";
			}
			$query .= sprintf("('%s', '%s', '%s', '%s')", $module, $key, mysql_real_escape_string($value), $user);
			$i++;
		}
		if ($i > 0) {
			db_query($query, $link);
		}
		disconnect($link);
		return true;
	}
	
	function removeSettings($module, $user = "system", $db_settings = false) {
		$link = connect($db_settings);
		$affected = db_delete($this->table, array('module' => $module, 'user' => $user), $link);
		disconnect($link);
		return $affected;
	}
}

?>