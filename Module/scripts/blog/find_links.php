<?php
header("Content-type: text/plain; charset=utf-8");
include($_SERVER['DOCUMENT_ROOT']."/includes/functions.php");

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM blog_content
					WHERE content REGEXP 'item.php\?'");
echo $rs->getNum();
				
	while ($row = $rs->getRow())
	{
	$o_matches = array();
	$o_replaces = array();
	
	$content = $row['content'];
	preg_match_all("/\/en\/blog\/item.php\?blog_id=.+?#/", $content, $matches);
	//print_r($matches[0]);
	
		foreach($matches[0] as $match)
		{
		$match = trim($match, ' #');
		$o_matches[] = $match;
		
		$m_pieces = explode('=', $match);
		
		$o_replaces[] = "/en/blog/item/{$m_pieces[1]}/";
		}
		
	$output[] = array(	'blog_id' => $row['blog_id'],
						'matches' => $o_matches,
						'replaces' => $o_replaces,
						'content' => addslashes(str_replace($o_matches, $o_replaces, $content))
						);
	}
	
	foreach ($output as $o)
	{
	//print_r($o);
	$db->execute("	UPDATE blog_content
					SET content = '{$o['content']}'
					WHERE blog_id={$o['blog_id']}");
	}

echo 'done';
?>
