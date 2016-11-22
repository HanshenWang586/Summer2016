<?php
class AdminModule {

	private $user_ids = array();

	public function __construct($module_id = '') {
		if (ctype_digit($module_id)) {
			$this->module_id = $module_id;
			$db = new DatabaseQuery;
			$rs = $db->execute('	SELECT *
									FROM admin_modules
									WHERE module_id = '.$this->module_id);
			$this->setData($rs->getRow());

			// get users who have access to the given module
			$rs = $db->execute('	SELECT user_id
									FROM admin_permissions
									WHERE module_id = '.$this->module_id);

			while ($row = $rs->getRow())
				$this->user_ids[] = $row['user_id'];
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function displayForm() {
		$content .= FormHelper::open('form_module_proc.php');
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('module_id', $this->module_id);
		
		$f[] = FormHelper::input('Module', 'menu_text', $this->menu_text);
		$f[] = FormHelper::input('Module Key', 'module_key', $this->module_key);
		$f[] = FormHelper::input('URL', 'menu_link', $this->menu_link);
		$f[] = FormHelper::checkbox('Open', 'open_to_all', $this->open_to_all, array('disabled' => true));
		$f[] = FormHelper::checkbox_array('Access', 'user_ids', AdminUserList::getArray(), $this->user_ids);
		
		$content .= FormHelper::fieldset('Module', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		return $content;
	}

	public function save() {
		$db = new DatabaseQuery;

		if ($this->module_id != '') {
			$db->execute("	UPDATE admin_modules
							SET module_key = '".$db->clean($this->module_key)."',
								menu_text = '".$db->clean($this->menu_text)."',
								menu_link = '".$db->clean($this->menu_link)."'
							WHERE module_id = $this->module_id");
		}
		else {
			$db->execute("	INSERT INTO admin_modules (	module_key,
														menu_text,
														menu_link,
														open_to_all)
							VALUES ('".$db->clean($this->module_key)."',
									'".$db->clean($this->menu_text)."',
									'".$db->clean($this->menu_link)."',
									0)");
			$this->module_id = $db->getNewID();
		}

		$db->execute('	DELETE FROM admin_permissions
						WHERE module_id = '.$this->module_id);

		foreach($this->user_ids as $user_id) {
			$db->execute("	INSERT INTO admin_permissions (	user_id,
															module_id)
							VALUES (	$user_id,
										$this->module_id)");
		}
	}
}
?>