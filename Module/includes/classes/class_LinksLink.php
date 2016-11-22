<?php
class LinksLink
{
	function __construct($link_id = '')
	{
		if (ctype_digit($link_id))
		{
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM links_links
							WHERE link_id=$link_id");
		$row = $rs->getRow();
		$this->setData($row);
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
	
	function display_public_row()
	{
	$content = "<a href=\"$this->url\" target=\"_blank\"><b>$this->name</b></a>".($this->description!='' ? "<br />$this->description" : '')."<br /><br />";
	return $content;
	}
	
	function display_admin_row()
	{
	$content = "<tr valign=\"top\">
	<td>$this->name</td>
	<td>".htmlentities($this->url)."</td>
	<td width=\"200\">$this->description</td>
	<td><a href=\"form_link.php?link_id=$this->link_id&folder_id=$this->folder_id\">Edit</a></td>
	<td><a href=\"delete_link.php?link_id=$this->link_id\" onClick=\"return conf_del()\">Delete</a></td>
	</tr>";
	return $content;
	}
	
	
	function display_form($folder_id)
	{
	$content = "<form action=\"form_link_proc.php\" method=\"post\">
	<input type=\"hidden\" name=\"folder_id\" value=\"$folder_id\">
	<input type=\"hidden\" name=\"link_id\" value=\"$this->link_id\">
	<table cellspacing=\"1\" class=\"gen_table\">
	<tr><td><b>Name</b></td><td><input name=\"name\" value=\"$this->name\" size=\"60\"></td></tr>
	<tr><td><b>URL</b></td><td><input name=\"url\" value=\"$this->url\" size=\"60\"></td></tr>
	<tr valign=\"top\"><td><b>Description</b></td><td><textarea name=\"description\" cols=\"55\" rows=\"12\">$this->description</textarea></td></tr>
	</table><br />
	<input type=\"submit\" value=\"Save\">
	</form>";
	return $content;
	}
	
	function save()
	{
	$db = new DatabaseQuery;
	$cc = new ContentCleaner;
	$cc->cleanForDatabase($this->name);
	$cc->cleanForDatabase($this->description);
	
		if ($this->link_id)
		{
		$db->execute("	UPDATE links_links
						SET name='$this->name',
							url='".trim($this->url)."',
							description='$this->description'
						WHERE link_id=$this->link_id");
		}
		else
		{
		$db->execute("	INSERT INTO links_links (	folder_id,
													name,
													url,
													description
													)
						VALUES (	$this->folder_id,
									'$this->name',
									'".trim($this->url)."',
									'$this->description'
								)");
		}
	}
	
	function delete()
	{
	$db = new DatabaseQuery;
	$db->execute("	DELETE FROM links_links
					WHERE link_id=$this->link_id");
	}
}
?>