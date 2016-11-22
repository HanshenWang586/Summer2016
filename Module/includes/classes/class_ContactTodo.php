<?php
class ContactTodo
{
	function __construct($todo_id = '') {
		
		if (ctype_digit($todo_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
								FROM contact_todos
								WHERE todo_id=$todo_id");
			$this->setData($rs->getRow());
		}
	}
	
	function setData($data) {
	
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}
	
	function setContactID($contact_id) {
		$this->contact_id = $contact_id;
	}

	function setUserID($user_id) {
		$this->user_id = $user_id;
	}

	function setType($type) {
		$this->type = $type;
	}

	function displayForm() {
		global $admin_user;
	
		$aul = new AdminUserList;
	
		$dt_control = new DateTimeControl;
		$dt_control->setYearType('select');
		$dt_control->setPrefix('ts_target');
		$dt_control->setDateLabel('Date: ');
		$dt_control->disableTime();
		
		$content .= "<h1>Add ".ucfirst($this->type)."</h1>
		<form id=\"form_add_todo\">
		<input type=\"hidden\" name=\"todo_id\" value=\"$this->todo_id\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"action\" value=\"add_todo\">
		<input type=\"hidden\" name=\"type\" value=\"$this->type\">
		
		".$dt_control->display()."<br />
		Assign to: ".$aul->displaySelect($admin_user->getUserID())."<br />
		<textarea name=\"todo\" style=\"width: 100%; height: 100px; margin-top: 5px; margin-bottom: 5px; border: 1px solid #555; padding:2px; font-size: 12px;\"></textarea><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_add_todo')\">
		</form>";
		
		return $content;
	}

	function save() {
	
		$db = new DatabaseQuery;
		$this->ts_target = $this->ts_target_yyyy.'-'.$this->ts_target_mm.'-'.$this->ts_target_dd;
		
		if (ctype_digit($this->todo_id)) {
		$db->execute("	UPDATE contact_todos
						SET user_id=$this->user_id,
							todo='$this->todo',
							ts_target='$this->ts_target'
						WHERE todo_id=$this->todo_id");
		}
		else {
		$db->execute("	INSERT INTO contact_todos (	type,
													contact_id,
													user_id,
													todo,
													ts_target,
													ts)
						VALUES (	'$this->type',
									$this->contact_id,
									$this->user_id,
									'$this->todo',
									'$this->ts_target',
									NOW())");
		}
	}

	function display() {
	return '<b>'.$this->ts.' by '.$this->display_name.'</b><br />'.nl2br($this->note);
	}
}
?>