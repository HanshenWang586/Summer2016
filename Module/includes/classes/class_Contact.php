<?php
class Contact
{

	function __construct($contact_id = '')
	{
		if (ctype_digit($contact_id))
		{
		$this->contact_id = $contact_id;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM contacts
							WHERE contact_id=$this->contact_id");
		$this->setData($rs->getRow());
		}
	}
	
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
	
	function setContactID($contact_id)
	{
	$this->contact_id = $contact_id;
	}
	
	function getContactID()
	{
	return $this->contact_id;
	}
	
	function getBriefName() {
	
		if ($this->type == 'organisation') {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT * FROM names WHERE contact_id=$this->contact_id AND type='organisation_en'");
		$row = $rs->getRow();
		return "<a href=\"contact.php?contact_id=$this->contact_id\">{$row['given_name']}</a>";
		}
		else {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT * FROM names WHERE contact_id=$this->contact_id AND type='givenfamily_en'");
		$row = $rs->getRow();
		return "<a href=\"contact.php?contact_id=$this->contact_id\">{$row['given_name']} {$row['family_name']}</a>";
		}
	}
	
	function addAssociate($contact) {
		$db = new DatabaseQuery;
		$db->execute("	DELETE FROM contacts_associations
						WHERE id_1 = $this->contact_id AND id_2=".$contact->getContactID());
		$db->execute("	DELETE FROM contacts_associations
						WHERE id_2 = $this->contact_id AND id_1=".$contact->getContactID());
		
		$db->execute("	INSERT INTO contacts_associations (id_1, id_2)
						VALUES ($this->contact_id, ".$contact->getContactID().")");
		$db->execute("	INSERT INTO contacts_associations (id_2, id_1)
						VALUES ($this->contact_id, ".$contact->getContactID().")");
	}
	
	function save()
	{
	global $admin_user;
	
		if (!$this->contact_id)
		{
		$db = new DatabaseQuery;
		$db->execute("	INSERT INTO contacts (type, user_id, ts)
						VALUES ('$this->type', ".$admin_user->getUserID().", NOW())");
		$this->contact_id = $db->getNewID();
		
		$name = new ContactName;
		$name->setContactID($this->contact_id);
		$name->setType($this->type == 'organisation' ? 'organisation_en' : 'givenfamily_en');
		$name->setGivenName($this->given_name);
		$name->setFamilyName($this->family_name);
		$this->addName($name);
		}
	
	$this->bundle();
	}
	
	function bundle()
	{
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM names
						WHERE contact_id=$this->contact_id");
					
		while ($row = $rs->getRow())
		{
		$text[] = $row['given_name'];
		$text[] = $row['family_name'];
		}
		
	$rs = $db->execute("SELECT *
						FROM coords
						WHERE contact_id=$this->contact_id");
					
		while ($row = $rs->getRow())
		{
		$text[] = $row['value'];
		}
		
	$db->execute("	UPDATE contacts
					SET bundle='".addslashes(implode(' ', $text))."'
					WHERE contact_id=$this->contact_id");
	}
	
	function display()
	{
	$content = $this->getNames();
	$content .= $this->getCoords();
	$content .= $this->getAssociations();
	$content .= $this->getNotes();						
	return $content;
	}
	
	function displayForm($type) {
	
		switch ($type) {
		case 'contact':
		$content ="<h1>Create Contact</h1>
		<form action=\"form_create_proc.php\" method=\"post\">
		<input type=\"hidden\" name=\"type\" value=\"contact\">

		Given name <input name=\"given_name\" onkeyup=\"rapidSearch(this.value, 'given_name')\"><br />
		Family name <input name=\"family_name\" onkeyup=\"rapidSearch(this.value, 'family_name')\"><br />
		<input type=\"submit\" value=\"Save\">
		</form>";
		break;
		
		case 'organisation':
		$content ="<h1>Create Organisation</h1>
		<form action=\"form_create_proc.php\" method=\"post\">
		<input type=\"hidden\" name=\"type\" value=\"organisation\">
		
		<input name=\"given_name\" onkeyup=\"rapidSearch(this.value, 'given_name')\"><br />
		<input type=\"submit\" value=\"Save\">
		</form>";
		}
	
	return $content;
	}
	
	function getActionPanel()
	{
	$content = "<div id=\"loading\" style=\"display: none;\">loading...</div>
	
	<div id=\"action_panel\">
	".$this->getDefaultAction()."
	</div>
	
	<br />
	<br />
	<h1>Add</h1>
	
	<select onChange=\"if (this.value != '') {loadActionPanel(this.value, $this->contact_id)}\">
	<option value=\"\">Please select...</option>";
	
	if ($this->type == 'organisation') {
	$content .= "<option value=\"name.organisation_en\">Organisation > English name</option>
	<option value=\"name.organisation_zh\">Organisation > Chinese name</option>";
	}
	else {	
	$content .= "<option value=\"name.name_en\">Name > English name</option>
	<option value=\"name.name_zh\">Name > Chinese name</option>
	<option value=\"name.nickname_en\">Name > English nickname</option>
	<option value=\"name.nickname_zh\">Name > Chinese nickname</option>";
	}
	
	$content .= "<option value=\"coords.email\">Coords > Email</option>
	<option value=\"coords.website\">Coords > Website</option>
	<option value=\"coords.mobile\">Coords > Mobile</option>
	<option value=\"coords.fixed\">Coords > Fixed line</option>
	<option value=\"coords.fax\">Coords > Fax</option>
	<option value=\"coords.address\">Coords > Address</option>
	
	<option value=\"note\">Note</option>
	<option value=\"associate\">Associate</option>
	
	<option value=\"callback\">Callback</option>
	</select>";
	
	return $content;
	}
	
	function getDefaultAction()
	{
	$note = new ContactNote;
	$note->setContactID($this->contact_id);
	return $note->displayForm();
	}
	
	function getNames()
	{
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM names
						WHERE contact_id=$this->contact_id");
						
	$content .= "<div id=\"names\">
	<table>";
						
		while ($row = $rs->getRow())
		{
			switch ($row['type'])
			{
			case 'givenfamily_en':
			$name = $row['given_name']." ".strtoupper($row['family_name']);
			$hack = 'name_en';
			break;
			
			case 'givenfamily_zh':
			$name = $row['family_name'].$row['given_name'];
			$hack = 'name_zh';
			break;
			
			case 'nickname_en':
			case 'nickname_zh':
			case 'organisation_en':
			$name = $row['given_name'];
			$hack = $row['type'];
			break;
			}
			
		$content .= "<tr>
		<td><h1>$name</h1></td>
		<td width=\"40\"><a href=\"javascript:void(null);\" onClick=\"processDelete('name', $this->contact_id, {$row['name_id']})\">Delete</a></td>
		<td width=\"10\"><a href=\"javascript:void(null);\" onClick=\"loadActionPanel('name.$hack', $this->contact_id, {$row['name_id']})\">Edit</a></td>
		</tr>";
		}
		
	$content .= "
	</table>	
	</div>";
	
	return $content;
	}
	
	function getCoords()
	{
	$content .= "
	<div id=\"coords\">";
	
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM coords
						WHERE contact_id=$this->contact_id
						ORDER BY type, value");
	
	$num = $rs->getNum();
		
		if ($num)
		{
		$content .= "<table>";

			while ($row = $rs->getRow())
			{
			++$i;
			
				if ($row['type'] == 'email') {
				$value = "<a href=\"mailto:{$row['value']}?bcc=db@gokunming.com\">{$row['value']}</a>";
				}
				else if ($row['type'] == 'address') {
				$address = ContentCleaner::wrapChinese($row['value']);
				$address = implode(',<br />', explode(',', $address));
				$value = $address;
				}
				else if ($row['type'] == 'website') {
				$value = "<a href=\"http://{$row['value']}\" target=\"_blank\">{$row['value']}</a>";
				}
				else {
				$value = $row['value'];
				
					if (strpos($value, '1') === 0 && strlen($value) == 11) {
						$value = substr($value, 0, 3).' '.substr($value, 3, 4).' '.substr($value, 7, 4);
					}
					
					if ($row['country_id'] != 1) {
						$db = new DatabaseQuery;
						$rs_2 = $db->execute("SELECT *
											FROM countries
											WHERE country_id={$row['country_id']}");
						$row_2 = $rs_2->getRow();
						
						if (substr($row['value'], 0, 1) == '0') {
							$value = '('.substr($row['value'], 0, 1).')'.substr($row['value'], 1);
						}
						
						$value = $row_2['country_en'].' +'.$row_2['country_code'].' '.$value;
					}
					
					if ($row['type'] == 'fax') {
						$value = 'Fax: '.$value;
					}	
				}
				
			$class = $i == $num ? " class=\"last\"" : '';
				
			$content .= "<tr>
			<td$class>$value</td>
			<td width=\"40\"$class><a href=\"javascript:void(null);\" onClick=\"processDelete('coords.{$row['type']}', $this->contact_id, {$row['coord_id']})\">Delete</a></td>
			<td width=\"10\"$class><a href=\"javascript:void(null);\" onClick=\"loadActionPanel('coords.{$row['type']}', $this->contact_id, {$row['coord_id']})\">Edit</a></td>
			</tr>";
			}
			
		$content .= "</table>";
		}
		
	$content .= "</div>";
	return $content;
	}
	
	function getAssociations()
	{
	$content .= "
	<div id=\"associations\">";
	
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT c.contact_id
						FROM contacts c, contacts_associations a
						WHERE a.id_1=$this->contact_id
						AND c.contact_id=a.id_2");
						
		if ($rs->getNum()) {
			$content .= "<h1>Associations</h1>";
				
			while ($row = $rs->getRow()) {
				$contact = new Contact($row['contact_id']);
				$content .= $contact->getBriefName().'<br />';
			}
		}
	
	$content .= "</div>";
	return $content;
	}
	
	function getNotes()
	{
	$content .= "
	<div id=\"notes\">";
	
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT n.*, display_name
						FROM notes n, admin_users u
						WHERE contact_id=$this->contact_id
						AND u.user_id=n.user_id
						ORDER BY ts DESC
						LIMIT 5");
						
		if ($rs->getNum())
		{
		$content .= "<h1>Notes</h1>
		<table>";
						
			while ($row = $rs->getRow())
			{
			$note = new ContactNote;
			$note->setData($row);
			$content .= "<tr><td>".$note->display()."</td></tr>";
			}
			
		$content .= "</table>";
		}
		
	$content .= "</div>";	
	return $content;
	}
	
	function addCoord($coord)
	{
	$coord->setContactID($this->contact_id);
	$coord->save();
	$this->bundle();
	}
	
	function addName($name)
	{
	$name->setContactID($this->contact_id);
	$name->save();
	$this->bundle();
	}
	
	function displayAssociateForm() {
	$content .= "<h1>Find Associates</h1>
	<form id=\"form_associates\">
	<input type=\"hidden\" name=\"action\" value=\"add_associates\">
	<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
	<input id=\"associate_search\" onkeyup=\"rapidSearchAssociates(this.value)\">
	
	<div id=\"associate_search_results\"></div>
	<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_associates')\">
	</form>";
	
	return $content;
	}
}
?>