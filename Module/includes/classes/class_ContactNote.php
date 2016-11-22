<?php
class ContactNote
{
	function setData($data)
	{
		if (is_array($data))
		{
			foreach ($data as $key => $value)
			{
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

	function displayForm()
	{
		$content .= "<h1>Add Note</h1>
		<form id=\"form_add_note\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"action\" value=\"add_note\">
		<textarea name=\"note\" style=\"width: 100%; height: 100px; margin-top: 5px; margin-bottom: 5px; border: 1px solid #555; padding:2px; font-size: 12px;\"></textarea><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_add_note')\">
		</form>";
		
		return $content;
	}

	function save() {
	
		if (trim($this->note)) {
		$db = new DatabaseQuery;
		$db->execute("	INSERT INTO notes (contact_id, user_id, note, ts)
						VALUES ($this->contact_id, $this->user_id, '$this->note', NOW())");
		}
	}

	function display() {
	return '<b>'.$this->ts.' by '.$this->display_name.'</b><br />'.nl2br($this->note);
	}
}
?>