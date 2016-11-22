<?php
class PhotoTagList
{
	function displayAdmin()
	{
	$db = new DatabaseQuery;
	$rs = $db->execute("	SELECT tag, COUNT(*) AS tally
							FROM photos_tags
							GROUP BY tag
							ORDER BY tag");

		while ($row = $rs->getRow())
		{
		$tags[] = "<a href=\"index.php?tag=".urlencode($row['tag'])."\">{$row['tag']}&nbsp;({$row['tally']})</a>";
		}

	$content = "<div style=\"width:500px;\">".implode(', ', $tags)."</div>";
	return $content;
	}
}
?>