<?php
class SideBlock
{
	function setDivID($div_id)
	{
	$this->div_id = $div_id;
	}

	function setContent($content)
	{
	$this->content = $content;
	}

	function display()
	{
	$content = "<div".($this->div_id != '' ? " id=\"$this->div_id\"" : '')." style=\"margin-bottom: 5px;\">";
	$content .= $this->content;
	$content .= "</div>";

	return $content;
	}
}
?>