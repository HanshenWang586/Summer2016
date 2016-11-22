<?
class PreferencesInstaller {
	var $folders = array();
	var $tables = array();
	// Other queries
	var $flags = array();
	var $queries = array(); // Flags correspond with the keys, if available, of queries and errormessages
	var $errorMessages = array();
	
	function PreferencesInstaller() {
		$table = getTableName('preferences');
		
		$this->tables[$table]['create'] = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
					`id` int(11) NOT NULL auto_increment, 
					PRIMARY KEY  (`id`)
				) TYPE=MyISAM PACK_KEYS=0;", $table);
		$this->tables[$table]['columns'] = array(
				'id' => sprintf("ALTER TABLE `%s` ADD `id` int(11) NOT NULL auto_increment, ADD PRIMARY KEY (`id`)", $table),
				'name' => sprintf("ALTER TABLE `%s` ADD `name` varchar(100) NOT NULL default ''", $table),
				'module' => sprintf("ALTER TABLE `%s` ADD `module` varchar(100) NOT NULL default '0'", $table),
				'user' => sprintf("ALTER TABLE `%s` ADD `user` varchar(20) NOT NULL default '0'", $table),
				'value' => sprintf("ALTER TABLE `%s` ADD `value` text NOT NULL", $table)
		);
  
		$flag = "name";
		$this->flags[] = $flag;
		$this->errorMessages[$flag] = sprintf("Duplicates in the preferences.", $table);
		$this->queries[$flag] = sprintf(" ALTER TABLE `%s` ADD UNIQUE KEY `name` (`name`,`module`,`user`)", $table);

		
	}
}	
?>
