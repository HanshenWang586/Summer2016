<?php
class ForumLatest {
	function getLatestAsUnorderedList() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM bb_threads
							WHERE locked=0
							AND live=1
							ORDER BY ts DESC
							LIMIT 7");
							
			if ($rs->getNum())
			{
			$content = '<ul>';
							
				while ($row = $rs->getRow())
				{
				$thread = new ForumThread;
				$thread->setData($row);
				$content .= '<li><a href="'.$thread->getURL().'">'.$thread->getTitle().'</a></li>';
				}
				
			$content .= '</ul>';
			}
			
		return $content;
	}
}
?>