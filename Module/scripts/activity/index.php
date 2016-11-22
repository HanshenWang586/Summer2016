<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

$periods = array(
				array(	'start' => '2006-01-01',
						'end'	=> '2006-12-31'),
				array(	'start' => '2007-01-01',
						'end'	=> '2007-12-31'),
				array(	'start' => '2008-01-01',
						'end'	=> '2008-12-31'),
				array(	'start' => '2009-01-01',
						'end'	=> '2009-12-31'),
				array(	'start' => '2010-01-01',
						'end'	=> '2010-12-31'),
				array(	'start' => '2011-01-01',
						'end'	=> '2011-06-30')
				);

foreach ($periods as $period) {
	$tally = 0;
	$db = new DatabaseQuery;
	
	// comments
	$rs = $db->execute("SELECT COUNT(*) AS tally
						FROM blog_comments c, blog_content i
						WHERE i.site_id = 1
						AND i.blog_id = c.blog_id
						AND c.live = 1
						AND c.ts >= '{$period['start']}'
						AND c.ts <= '{$period['end']}'");
	$row = $rs->getRow();
	$tally += $row['tally'];
	
	// reviews
	$rs = $db->execute("SELECT COUNT(*) AS tally
						FROM listings_reviews r, public_users u
						WHERE u.site_id = 1
						AND u.user_id = r.user_id
						AND r.live = 1
						AND r.ts >= '{$period['start']}'
						AND r.ts <= '{$period['end']}'");
	$row = $rs->getRow();
	$tally += $row['tally'];
	
	// forum posts
	$rs = $db->execute("SELECT COUNT(*) AS tally
						FROM bb_posts p, bb_threads t
						WHERE t.site_id = 1
						AND t.thread_id = p.thread_id
						AND p.live = 1
						AND t.live = 1
						AND p.ts >= '{$period['start']}'
						AND p.ts <= '{$period['end']}'");
	//echo $db->getLastQuery()."\n";
	$row = $rs->getRow();
	$tally += $row['tally'];
	
	// classifieds
	$rs = $db->execute("SELECT COUNT(*) AS tally
						FROM classifieds_data p
						WHERE p.site_id = 1
						AND p.status IN (1, 3, 5)
						AND p.ts >= '{$period['start']}'
						AND p.ts <= '{$period['end']}'");
	$row = $rs->getRow();
	$tally += $row['tally'];
	
	echo "{$period['start']} to {$period['end']}: $tally\n";
}

?>