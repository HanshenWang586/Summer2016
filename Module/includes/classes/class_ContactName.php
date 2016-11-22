<?php
class ContactName
{
	function __construct($name_id = '')
	{
		if (ctype_digit($name_id))
		{
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM names
							WHERE name_id=$name_id");
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
	
	function setType($type)
	{
	$this->type = $type;
	}
	
	function setGivenName($name)
	{
	$this->given_name = $name;
	}
	
	function setFamilyName($name)
	{
	$this->family_name = $name;
	}
	
	function displayForm($type)
	{
		switch ($type)
		{
		case 'name_en':
		return $this->displayNameEnForm();
		break;
		
		case 'name_zh':
		return $this->displayNameZhForm();
		break;
		
		case 'nickname_en':
		return $this->displayNicknameEnForm();
		break;
	
		case 'nickname_zh':
		return $this->displayNicknameZhForm();
		break;
		
		case 'organisation_en':
		return $this->displayOrganisationEnForm();
		break;
		
		case 'organisation_zh':
		return $this->displayOrganisationZhForm();
		break;

		}
	}
	
	function displayNameEnForm()
	{
	$content = "<h1>Add/Edit English Name</h1>
	<form id=\"form_name_en\">
	<input type=\"hidden\" name=\"type\" value=\"givenfamily_en\">
	<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
	<input type=\"hidden\" name=\"name_id\" value=\"$this->name_id\">
	<input type=\"hidden\" name=\"action\" value=\"name_en\">
	
	Given name: <input name=\"given_name\" value=\"$this->given_name\"><br />
	Family name: <input name=\"family_name\" value=\"$this->family_name\"><br />
	<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_name_en')\">
	</form>";
	
	return $content;
	}
	
	function displayOrganisationEnForm()
	{
	$content = "<h1>Add/Edit Organisation</h1>
	<form id=\"form_organisation_en\">
	<input type=\"hidden\" name=\"type\" value=\"organisation_en\">
	<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
	<input type=\"hidden\" name=\"name_id\" value=\"$this->name_id\">
	<input type=\"hidden\" name=\"action\" value=\"organisation_en\">
	
	Organisation: <input name=\"given_name\" value=\"$this->given_name\"><br />
	<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_organisation_en')\">
	</form>";
	
	return $content;
	}
	
	function displayOrganisationZhForm()
	{
	$content = "<h1>Add/Edit Organisation</h1>
	<form id=\"form_organisation_zh\">
	<input type=\"hidden\" name=\"type\" value=\"organisation_en\">
	<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
	<input type=\"hidden\" name=\"name_id\" value=\"$this->name_id\">
	<input type=\"hidden\" name=\"action\" value=\"organisation_zh\">
	
	Organisation (C): <input name=\"given_name\" value=\"$this->given_name\"><br />
	<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_organisation_zh')\">
	</form>";
	
	return $content;
	}
	
	function displayNameZhForm()
	{
	$content = "<h1>Add/Edit Chinese Name</h1>
	<form id=\"form_name_zh\">
	<input type=\"hidden\" name=\"type\" value=\"givenfamily_zh\">
	<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
	<input type=\"hidden\" name=\"name_id\" value=\"$this->name_id\">
	<input type=\"hidden\" name=\"action\" value=\"name_zh\">
	
	Family name / 姓 <input name=\"family_name\" value=\"$this->family_name\"><br />
	Given name / 名 <input name=\"given_name\" value=\"$this->given_name\"><br />
	<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_name_zh')\">
	</form>";
	
	return $content;
	}
	
	function displayNicknameEnForm()
	{
	$content = "<h1>Add/Edit English Nickname</h1>
	<form id=\"form_nickname_en\">
	<input type=\"hidden\" name=\"type\" value=\"nickname_en\">
	<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
	<input type=\"hidden\" name=\"name_id\" value=\"$this->name_id\">
	<input type=\"hidden\" name=\"action\" value=\"nickname_en\">
	
	Nickname <input name=\"given_name\" value=\"$this->given_name\"><br />
	<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_nickname_en')\">
	</form>";
	
	return $content;
	}
	
	function displayNicknameZhForm()
	{
	$content = "<h1>Add/Edit Chinese Nickname</h1>
	<form id=\"form_nickname_zh\">
	<input type=\"hidden\" name=\"type\" value=\"nickname_zh\">
	<input type=\"hidden\" name=\"contact_id\" value=\"$this->contact_id\">
	<input type=\"hidden\" name=\"name_id\" value=\"$this->name_id\">
	<input type=\"hidden\" name=\"action\" value=\"nickname_zh\">
	
	Nickname / 外号 <input name=\"given_name\" value=\"$this->given_name\"><br />
	<input type=\"button\" value=\"Save\" onClick=\"submitActionPanelForm('form_nickname_zh')\">
	</form>";
	
	return $content;
	}
	
	function save()
	{
	$db = new DatabaseQuery;
	
		if (ctype_digit($this->name_id))
		{
		$db->execute("	UPDATE names
						SET given_name = '$this->given_name',
							family_name = '$this->family_name'
						WHERE name_id=$this->name_id");
		}
		else
		{
		$db->execute("	INSERT INTO names (	contact_id,
												type,
												given_name,
												family_name)
							VALUES ($this->contact_id,
									'$this->type',
									'$this->given_name',
									'$this->family_name')");
		}
	}
	
	function delete()
	{
		if (ctype_digit($this->name_id))
		{
		$db = new DatabaseQuery;
		$db->execute("	DELETE FROM names
						WHERE name_id=$this->name_id");
		}
	}
}
?>