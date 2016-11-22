<?php
class Content
{
var $sorry = array(	1=>'',
					2=>'對不起, 這部分內容暫時沒有繁體中文版');

	function set_language_id($language_id)
	{
	$this->language_id = $language_id;
	}
	
	function get_content($content_id)
	{
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT content
						FROM web_content
						WHERE content_id=$content_id");
		
		if ($rs->getNum()==0)
		{
		$content .= $this->sorry[1]."<br /><br />";
		}
	
	$row = $rs->getRow();
	$content .= $row['content'];
	return $content;
	}
}
?>