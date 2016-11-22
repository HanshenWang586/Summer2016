<?php
class GeneralCMS
{
	function __construct($content_id = '') {
	
		if (ctype_digit($content_id))
		{
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT * FROM web_content WHERE content_id=$content_id");
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
	
	function getContentID() {
		return $this->content_id;
	}
	
	function display_search($content_id='')
	{
	$content = "<form action=\"index.php\" method=\"get\">
	<table cellspacing=\"1\" class=\"gen_table\">
	<tr><td><b>Content ID</b></td><td><input name=\"content_id\" value=\"$content_id\"></td></tr>
	</table><br />
	<input type=\"submit\" value=\"Search\">
	</form>";
	return $content;
	}
	
	function display_results($content_id)
	{
		if ($content_id=='')
		{
		return '';
		}
	$db = new DatabaseQuery;
	$rs = $db->execute("	SELECT *
							FROM web_content c
							WHERE content_id=$content_id");
					
		if ($rs->getNum() != 0)
		{
		$content = "<table cellspacing=\"1\" class=\"gen_table\">";
		
			while ($row = $rs->getRow())
			{
			$content .= "<tr valign=\"top\">
			<td>{$row['content_id']}</td>
			<td>".nl2br(htmlentities(stripslashes($row['content']), ENT_COMPAT, 'UTF-8'))."</td>
			<td><a href=\"form_content.php?content_id=$content_id\">Edit</a></td>
			</tr>";
			}
		$content .= "</table>";
		}

	return $content;
	}
	
	function displayForm()
	{
	$db = new DatabaseQuery;
	$content = "<form action=\"form_content_proc.php\" method=\"post\">
	<table cellspacing=\"1\" class=\"gen_table\">
	<tr><td><b>Content ID</b></td><td>".(ctype_digit($this->content_id) ? "<input type=\"hidden\" name=\"content_id\" value=\"$this->content_id\">$this->content_id" : "AUTO")."</td></tr>
	<tr valign=\"top\"><td><b>Content</b></td><td><textarea name=\"content\" cols=\"90\" rows=\"30\">".htmlentities(stripslashes($this->content), ENT_COMPAT, 'UTF-8')."</textarea></td></tr>
	</table><br />
	<input type=\"submit\" value=\"Save\">
	</form>";
	return $content;
	}
	
	function save()
	{
	$content = trim($this->content);
	
	$db = new DatabaseQuery;
	
		if (!ctype_digit($this->content_id))
		{
		$db->execute("	INSERT INTO web_content (	content)
						VALUES (	'$this->content')");
		}
		else
		{
		$db->execute("	UPDATE web_content
						SET content='$this->content'
						WHERE content_id=$this->content_id");
		}
	
	$this->content_id = $db->getNewID();
	}
}
?>