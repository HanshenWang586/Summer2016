<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('forum');

$sites = SiteList::getArray();
foreach ($sites as $site_id => $site_name) {
	$dates = array();
	$threads_created_total = 0;
	$threads_active_total = 0;
	$posts_total = 0;
	$i = 1;
	
	$body .= '<h2>'.$site_name.'</h2>';
	$header = array('Year-Month',
					'Threads Created',
					'Threads Active',
					'Posts');
	$body .= '<table class="main">';
	$body .= HTMLHelper::wrapArrayInTh($header);
	
	// prepare date range
	$db = new DatabaseQuery;
	$rs = $db->execute('SELECT DATE_FORMAT(MIN(ts_created), \'%Y-%m\') AS date_start
						FROM bb_threads
						WHERE site_id = '.$site_id);
	$row = $rs->getRow();
	$date_start = DateTime::createFromFormat('!Y-m', $row['date_start']);
	$date_end = new DateTime();
	$interval = new DateInterval('P1M'); // 1 month
	$period = new DatePeriod($date_start, $interval, $date_end);
	
	foreach($period as $dt)
		$dates[] = $dt->format('Y-m');
	$dates = array_reverse($dates);
	
	foreach($dates as $date) {
		$rs = $db->execute("SELECT COUNT(*) AS threads_created
							FROM bb_threads t
							WHERE live = 1
							AND site_id = $site_id
							AND DATE_FORMAT(ts_created, '%Y-%m') = '$date'");
		$row = $rs->getRow();
		$threads_created = $row['threads_created'];
		$threads_created_total += $threads_created;
		
		$rs = $db->execute("SELECT COUNT(*) AS threads_active
							FROM bb_threads t
							WHERE live = 1
							AND site_id = $site_id
							AND DATE_FORMAT(ts, '%Y-%m') = '$date'");
		$row = $rs->getRow();
		$threads_active = $row['threads_active'];
		$threads_active_total += $threads_active;
		
		$rs = $db->execute("SELECT COUNT(*) AS posts
							FROM bb_posts p, bb_threads t
							WHERE t.live = 1
							AND p.live = 1
							AND site_id = $site_id
							AND t.thread_id = p.thread_id
							AND DATE_FORMAT(p.ts, '%Y-%m') = '$date'");
		$row = $rs->getRow();
		$posts = $row['posts'];
		$posts_total += $posts;
		$scale = date('t')/date('j');
		
		$trow = array(	$date,
						number_format($threads_created).($i == 1 ? ' ('.number_format(round($threads_created*$scale)).') ' : ''),
						number_format($threads_active).($i == 1 ? ' ('.number_format(round($threads_active*$scale)).') ' : ''),
						number_format($posts).($i == 1 ? ' ('.number_format(round($posts*$scale)).') ' : ''));
		$body .= HTMLHelper::wrapArrayInTr($trow);
		$i++;
	}
	
	$trow = array(	'Total',
					number_format($threads_created_total),
					number_format($threads_active_total),
					number_format($posts_total));
	$body .= HTMLHelper::wrapArrayInTr($trow);
	$body .= '</table>';
}

$pap->setTag('main', $body);
$pap->output();
?>