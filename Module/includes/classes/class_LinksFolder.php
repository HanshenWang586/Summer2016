<?php
class LinksFolder
{
	function __construct($folder_id = '')
	{
		if (ctype_digit($folder_id))
		{
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM links_folders
							WHERE folder_id=$folder_id");
		$row = $rs->getRow();
		$this->setData($row);
		}
		else
		{
		$this->folder_id = 0;
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
	
	function display_admin()
	{
		if ($this->folder_id!=0)
		{
		$content .= "<b>$this->folder</b><br /><br />";
		}
	
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM links_folders
						WHERE parent_id=$this->folder_id
						ORDER BY folder ASC");
						
		if ($rs->getNum()>0)
		{
		$content .= "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>Folder ID</b></td>
		<td><b>Folder</b></td>
		</tr>";
		
			while ($row = $rs->getRow())
			{
			$li = new LinksFolder;
			$li->setData($row);
			$content .= $li->display_admin_row();
			}
			
		$content .= "</table>";
		}
		else
		{
		$content .= "<a href=\"form_link.php?folder_id=$this->folder_id\">Add a link here</a><br /><br />";
		$rs = $db->execute("SELECT *
							FROM links_links
							WHERE folder_id=$this->folder_id
							ORDER BY name ASC");
							
		$content .= "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>Name</b></td>
		<td><b>URL</b></td>
		<td><b>Description</b></td>
		<td colspan=\"2\"></td>
		</tr>";
		
			while ($row = $rs->getRow())
			{
			$li = new LinksLink;
			$li->setData($row);
			$content .= $li->display_admin_row();
			}
			
		$content .= "</table>";
		}
	return $content;
	}
	
	function display_admin_row()
	{
	return "<tr>
	<td>$this->folder_id</td>
	<td><a href=\"index.php?folder_id=$this->folder_id\">$this->folder</a></td>
	</tr>";
	}
	
	function display_contents()
	{
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM links_links
						WHERE folder_id=$this->folder_id
						ORDER BY name ASC");
						
		while ($row = $rs->getRow())
		{
		$li = new LinksLink;
		$li->setData($row);
		$content .= $li->display_public_row();
		}
		
	return $content;
	}
}
?>