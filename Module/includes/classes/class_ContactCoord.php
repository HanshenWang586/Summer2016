<?php
class ContactCoord
{
	function __construct($coord_id = '')
	{
		if (ctype_digit($coord_id))
		{
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM coords
							WHERE coord_id=$coord_id");
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
	
	function saveAddress()
	{
	$db = new DatabaseQuery;
	
		if (ctype_digit($this->coord_id))
		{
		$db->execute("	UPDATE coords
						SET value='$this->value'
						WHERE coord_id=$this->coord_id");
		}
		else
		{
		$db->execute("	INSERT INTO coords (	contact_id,
												type,
												value)
						VALUES ($this->contact_id,
								'$this->type',
								'$this->value')");
		}
		
	$contact = new Contact($this->contact_id);
	$contact->bundle();
	}
	
	function save()
	{
	$this->value = str_replace(' ', '', trim(strtolower($this->value)));
	$this->country_id = !isset($this->country_id) ? 0 : $this->country_id;
	$db = new DatabaseQuery;
	
		if (ctype_digit($this->coord_id))
		{
		$db->execute("	UPDATE coords
						SET value='$this->value',
							country_id=$this->country_id
						WHERE coord_id=$this->coord_id");
		}
		else
		{
		$db->execute("	INSERT INTO coords (	contact_id,
												type,
												value,
												country_id)
						VALUES ($this->contact_id,
								'$this->type',
								'$this->value',
								$this->country_id)");
		}
		
	$contact = new Contact($this->contact_id);
	$contact->bundle();
	}
	
	function delete()
	{
		if (ctype_digit($this->coord_id))
		{
		$db = new DatabaseQuery;
		$db->execute("	DELETE FROM coords
						WHERE coord_id=$this->coord_id");
		}
	}
	
	function displayForm($type)
	{
		switch ($type)
		{
		case 'email':
		return $this->displayEmailForm();
		break;
		
		case 'address':
		return $this->displayAddressForm();
		break;
		
		case 'website':
		return $this->displayWebsiteForm();
		break;
		
		case 'mobile':
		return $this->displayMobileForm();
		break;
		
		case 'fixed':
		return $this->displayFixedLineForm();
		break;
		
		case 'fax':
		return $this->displayFaxForm();
		break;
		}
	}
	
	function displayAddressForm() {
	
		$content = "<h1>Add/Edit Address</h1>
		<form id=\"form_address\">
		<input type=\"hidden\" name=\"type\" value=\"address\">
		<input type=\"hidden\" name=\"action\" value=\"add_address\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"coord_id\" value=\"$this->coord_id\">
		Address: <textarea name=\"value\" rows=\"5\" cols=\"60\">$this->value</textarea><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_address')\">
		</form>";
		
		return $content;
	}
	
	function displayEmailForm() {
	
		$content = "<h1>Add/Edit Email</h1>
		<form id=\"form_email\">
		<input type=\"hidden\" name=\"type\" value=\"email\">
		<input type=\"hidden\" name=\"action\" value=\"add_email\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"coord_id\" value=\"$this->coord_id\">
		Email: <input name=\"value\" value=\"$this->value\"><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_email')\">
		</form>";
		
		return $content;
	}
	
	function displayWebsiteForm() {
	
		$content = "<h1>Add/Edit Website</h1>
		<form id=\"form_website\">
		<input type=\"hidden\" name=\"type\" value=\"website\">
		<input type=\"hidden\" name=\"action\" value=\"add_website\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"coord_id\" value=\"$this->coord_id\">
		
		Website: <input name=\"value\" value=\"$this->value\"><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_website')\">
		</form>";
		
		return $content;
	}
	
	function displayMobileForm() {
	
		$content = "<h1>Add/Edit Mobile</h1>
		<form id=\"form_mobile\">
		<input type=\"hidden\" name=\"type\" value=\"mobile\">
		<input type=\"hidden\" name=\"action\" value=\"add_mobile\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"coord_id\" value=\"$this->coord_id\">
		Country code: ".$this->getCountryCodeSelect()."<br />
		Mobile: <input name=\"value\" value=\"$this->value\"><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_mobile')\">
		</form>";
		
		return $content;
	}
	
	function displayFixedLineForm() {
	
		$content = "<h1>Add/Edit Fixed Line</h1>
		<form id=\"form_fixed\">
		<input type=\"hidden\" name=\"type\" value=\"fixed\">
		<input type=\"hidden\" name=\"action\" value=\"add_fixed\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"coord_id\" value=\"$this->coord_id\">
		Country code: ".$this->getCountryCodeSelect()."<br />
		Fixed line: <input name=\"value\" value=\"$this->value\"><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_fixed')\">
		</form>";
		
		return $content;
	}
	
	function displayFaxForm() {
	
		$content = "<h1>Add/Edit Fax</h1>
		<form id=\"form_fax\">
		<input type=\"hidden\" name=\"type\" value=\"fax\">
		<input type=\"hidden\" name=\"action\" value=\"add_fax\">
		<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
		<input type=\"hidden\" name=\"coord_id\" value=\"$this->coord_id\">
		Country code: ".$this->getCountryCodeSelect()."<br />
		Fax: <input name=\"value\" value=\"$this->value\"><br />
		<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_fax')\">
		</form>";
		
		return $content;
	}
	
	function getCountryCodeSelect() {
	
		$this->country_id = $this->country_id ? $this->country_id : 1;
		$content .= "<select name=\"country_id\">";
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT country_id, country_code FROM countries ORDER BY country_code");
		
			while ($row = $rs->getRow()) {
				$content .= "<option value=\"{$row['country_id']}\"".($row['country_id']==$this->country_id ? ' selected' : '').">{$row['country_code']}</option>";
			}
		
		$content .= "</select>";
		
		return $content;
	}
}