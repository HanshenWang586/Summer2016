<?php
class Calendar {
	private $date;
	public $categories = array('nightlife', 'live-music', 'dining', 'entertainment', 'culture', 'drinks', 'conventions', 'activity', 'specials');
	public $types = array('one-day', 'multiple-days', 'weekly');
	private $catLang;
	private $typesLang;
	
	public function getCategories() {
		global $model;
		if (!$this->catLang) {
			$this->catLang = array();
			foreach($this->categories as $cat) {
				$this->catLang[$cat] = $model->lang('CAT_' . strtoupper($cat), 'CalendarModel');
			}
		}
		return $this->catLang;
	}
	
	public function getTypes() {
		global $model;
		if (!$this->typesLang) {
			$this->typesLang = array();
			foreach($this->types as $type) {
				$this->typesLang[$type] = $model->lang('TYPE_' . strtoupper($type), 'CalendarModel');
			}
		}
		return $this->typesLang;
	}
	
	public function eventExists($id) {
		return $GLOBALS['model']->db()->count('calendar_events', array('calendar_id' => $id)) > 0;
	}
	
	private function getQueryLang() {
		global $model;
		$lang = strtolower($model->lang);
		if ($lang == 'cn') $lang = 'zh';
		return sprintf('l.name_%s AS venue, l.address_%s AS address', $lang, $lang);
	}
	
	// Dumb ass function just to make sure the same info is received for all events
	public function getBasicQuery($hideUnapproved = false) {
		global $user;
		// Add extra checks if the user is not a super user
		if ($hideUnapproved) $args = 'AND e.live = 1 AND e.approved = 1 ';
		elseif ($user->getPower()) $args = '';
		else $args = sprintf('AND ((e.live = 1 AND e.approved = 1) OR e.user_created = %d)', $GLOBALS['user']->getUserID());
		$lang = $this->getQueryLang();
		return "
			SELECT e.*, $lang, l.phone, l.phone_code_override, l.mobile, l.latitude, l.longitude, l.city_id, p.province, c.city_en AS city, c.phone_code
			FROM calendar_events e
			LEFT JOIN listings_data l ON (l.listing_id = e.listing_id)
			LEFT JOIN listings_cities c ON (l.city_id = c.city_id)
			LEFT JOIN provinces p ON (c.province_id = p.province_id)
			WHERE l.status = 1 " . $args;
	}
	
	public function getUpcoming() {
		global $model, $user;
		
		$date = unixToDate();
		$lang = $this->getQueryLang();
		// regular events
		return $model->db()->run_select("
			SELECT e.*, $lang, l.phone, l.phone_code_override, l.mobile, l.latitude, l.longitude, l.city_id, p.province, c.city_en AS city, c.phone_code
			FROM calendar_events e
			RIGHT JOIN calendar_upcoming u ON (e.calendar_id = u.event_id)
			LEFT JOIN listings_data l ON (l.listing_id = e.listing_id)
			LEFT JOIN listings_cities c ON (l.city_id = c.city_id)
			LEFT JOIN provinces p ON (c.province_id = p.province_id)
			WHERE l.status = 1
			AND e.live = 1
			AND e.approved = 1
			AND (
				event_date >= '$date'
				OR end_date >= '$date'
				OR days != 0
			)
			AND u.end >= '$date'
			AND u.start <= '$date'
			ORDER BY event_date, starting_time, end_date"
		, false, array('callback' => array($this, 'processEvent')));
	}
	
	public function getRelated($event_id, $listing_id) {
		global $model;
		
		$query = sprintf($this->getBasicQuery(true) . "
			AND (event_date >= CURDATE() OR end_date > CURDATE() OR days != 0)
			AND calendar_id != %d
			AND l.listing_id = %d
			ORDER BY event_date
			LIMIT 3",
			$event_id,
			$listing_id
		);
		
		// regular events
		return $model->db()->run_select($query, false, array('callback' => array($this, 'processEvent')));
	}
	
	public function getListingEvents($listing_id) {
		$date = unixToDate();
		$query = $this->getBasicQuery() . 
			sprintf("
				AND e.listing_id = %d
				AND (
					event_date >= '$date'
					OR end_date >= '$date'
					OR days != 0
				)
				ORDER BY event_date, days, starting_time
			", $listing_id);
		return $GLOBALS['model']->db()->run_select($query
		, false, array('callback' => array($this, 'processEvent')));
	}
	
	public function getUserEvents($user_id) {
		$date = unixToDate();
		$query = $this->getBasicQuery() . 
			sprintf("
				AND e.user_created = %d
				AND (
					event_date >= '$date'
					OR end_date >= '$date'
					OR days != 0
				)
				ORDER BY event_date, days, starting_time
			", $user_id);
		return $GLOBALS['model']->db()->run_select($query
		, false, array('callback' => array($this, 'processEvent'), 'arrayGroupBy' => 'event_date'));
	}
	
	public function getEvents($date = false, $hideUnapproved = false, $sidebarOnly = false, $arrayGroupBy = 'group') {
		global $model;
		
		$options = array('callback' => array($this, 'processEvent'));
		if ($arrayGroupBy) $options['arrayGroupBy'] = 'group';
		if (!$date) $date = $this->getDate();
		$dayOfTheWeek = date('N', $date);
		$date = unixToDate($date);
		if ($sidebarOnly) $sidebar = 'AND sidebar = 1';
		return $model->db()->run_select($this->getBasicQuery($hideUnapproved) . "
			AND (
				event_date = '$date'
				OR (
					event_date <= '$date' AND end_date >= '$date'
				) OR FIND_IN_SET('$dayOfTheWeek',days)
			)
			$sidebar
			ORDER BY sidebar DESC, starting_time ASC, venue"
		, false, $options);
	}
	
	public function getApproveList() {
		$result = $GLOBALS['model']->db()->run_select($this->getBasicQuery() . "
			AND approved = 0
			ORDER BY event_date, days, starting_time"
		, false, array('callback' => array($this, 'processEvent'), 'arrayGroupBy' => 'event_date'));
		return $result;
	}
	
	public function getEvent($event_id = false) {
		global $model;
		
		if (!$event_id) return false;
		
		return $model->db()->run_select(sprintf($this->getBasicQuery() . "
			AND calendar_id = %d", $event_id),
			true,
			array('callback' => array($this, 'processEvent'))
		);
	}
	
	public function getEventURL($id, $title = false) {
		global $model;
		if (!$title) $title = $model->db()->query('calendar_events', array('calendar_id' => $id), array('selectField' => 'title'));
		return $model->url(array('m' => 'calendar', 'view' => 'event', 'id' => $id, 'name' => $title));
	}
	
	public function processEvent($event) {
		global $model;
		$view = new View();
		$event['datetime'] = strtotime($event['event_date'] . ' ' . $event['starting_time']);
		if ($event['datetime']) $event['iso_date'] = date($event['starting_time'] ? 'c' : 'Y-m-dO', $event['datetime']);
		$event['datetime_end'] = strtotime($event['end_date']);
		if ($event['datetime_end']) $event['iso_end_date'] = date('Y-m-dO', $event['datetime_end']);
		if ($event['end_date']) $event['starting_time_formatted'] = sprintf('%s – %s', date('M j', $event['datetime']), date('M j', $event['datetime_end']));
		elseif ($event['all_day']) $event['starting_time_formatted'] = $view->lang('ALL_DAY', 'CalendarModel');
		elseif ($event['starting_time']) $event['starting_time_formatted'] = date('g:ia', strtotime($event['starting_time']));
		else $event['starting_time_formatted'] = false;
		if ($event['end_date']) $event['date_formatted'] = sprintf('%s – %s', date('M j', $event['datetime']), date('M j', $event['datetime_end']));
		elseif ($event['days']) {
			if (!is_array($event['days'])) $event['days'] = explode(',', $event['days']);
			if (($event['number_days'] = count($event['days'])) == 1) {
				$event['date_formatted'] = $model->lang('WEEKLY_DAY_' . $event['days'][0], 'CalendarModel');
			} else { 
				$days = $model->tool('datetime')->getDays(true);
				sort($event['days']);
				$event['date_formatted'] = implode('/',array_select_keys($event['days'], $days));
			}
		}
		else $event['date_formatted'] = date('l M j', $event['datetime']);
		$event['url'] = $this->getEventURL($event['calendar_id'], $event['title']);
		$event['listing_url'] = $view->url(array('m' => 'listings', 'view' => 'item', 'id' => $event['listing_id'], 'name' => $event['venue']));
		$event['price_formatted'] = $event['price'] == -1 ? '' : ($event['price'] == 0 ? $view->lang('FREE_ENTRY', 'CalendarModel') : formatMoney($event['price']));
		$event['category_formatted'] = $event['category'] ? sprintf('<span class="category">%s</span>', $view->lang('CAT_' . strtoupper($event['category']), 'CalendarModel')) : '';
		$event['currency'] = $event['price'] > 0 ? ' ' . $view->lang('CURRENCY_CNY') : '';
		$event['description'] = ContentCleaner::PWrap(ContentCleaner::linkHashURLs($event['description']));
		$event['phone_formatted'] = $event['phone'] ? ($event['phone_code_override'] ? $event['phone'] : ('('.$event['phone_code'].') ' . $event['phone'])) : $event['mobile'];
		if (!$event['title']) {
			preg_match('/^([^.!?\s]*[\.!?\s]+){0,9}/', strip_tags($event['description']), $abstract);
			
			$event['title'] = trim(mb_substr($abstract[0], 0, 60, 'utf-8')) . '&hellip;';
		}
		if ($event['live'] == 0) $event['status'] = sprintf('<span class="eventStatus status-deleted"><span class="icon icon-warning"></span> <span class="caption">%s</span></span>', $view->lang('APPROVAL_DELETED', 'CalendarModel'));
		elseif ($event['approved'] == 0) $event['status'] = sprintf('<span class="eventStatus status-pending"><span class="icon icon-warning"></span> <span class="caption">%s</span></span>', $view->lang('APPROVAL_PENDING', 'CalendarModel'));
		elseif ($event['approved'] < 0) $event['status'] = sprintf('<span class="eventStatus status-rejected"><span class="icon icon-warning"></span> <span class="caption">%s</span></span>', $view->lang('APPROVAL_REJECTED', 'CalendarModel'));
		$event['_processed'] = true;
		return $event;
	}
	
	public function printThreeMonths() {
		$date = $this->getDate();
		$m2 = date('n', $date);
		$y2 = date('Y', $date);
		if ($m2 == 1) {
			$m1 = 12; $y1 = $y2 - 1;
			$m3 = $m2 + 1; $y3 = $y2;
		} elseif($m2 == 12) {
			$m3 = 1; $y3 = $y2 + 1;
			$m1 = $m2 - 1; $y1 = $y2;
		} else {
			$m1 = $m2 - 1;
			$m3 = $m2 + 1;
			$y1 = $y3 = $y2;
		}
		
		return $this->printMonth($m1, $y1, $date) . $this->printMonth($m2, $y2, $date) . $this->printMonth($m3, $y3, $date);
	}
	
	public function printMonth($month, $year, $currentDateEpoc) {
		global $model;
		/* draw table */
		$calendar = sprintf('<div class="calendarMonth"><span class="monthName">%s, %d</span><table cellpadding="0" cellspacing="0" class="calendar">', $model->tool('datetime')->getMonth($month), $year);
		/* table headings */
		$headings = $model->tool('datetime')->getDays(true, true);
		$calendar.= '<tr class="calendar-row"><th>'.implode('</th><th>',$headings).'</th></tr>';
	
		/* days and weeks vars now ... */
		$running_day = date('N',mktime(0,0,0,$month,1,$year));
		$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
		$days_in_this_week = 1;
		$day_counter = 0;
		$dates_array = array();
		$today = date('Y-m-d');
		$selected = date('Y-m-d', $currentDateEpoc);
	
		/* row for week one */
		$calendar.= '<tr class="calendar-row">';
	
		/* print "blank" days until the first of the current week */
		for ($x = 1; $x < $running_day; $x++) {
			$calendar.= '<td class="calendar-day-np"><span class="empty-date"> </span></td>';
			$days_in_this_week++;
		}
	
		/* keep going with days.... */
		$url = $model->url(array('m' => 'calendar', 'view' => 'date'));
		
		for ($list_day = 1; $list_day <= $days_in_month; $list_day++) {
			$calendar.= '<td class="calendar-day">';
			/* add in the day number */
			$date = date('Y-m-d', mktime(0,0,0,$month,$list_day,$year));
			$classes = array('url-date');
			if ($date == $today) $classes[] = 'today';
			if ($date == $selected) $classes[] = 'selected';
			if ($running_day == 6 or $running_day == 7) $classes[] = 'weekend';
			$calendar.= sprintf('<a href="%s%s/" class="%s">%s</a>', $url, $date, implode(' ', $classes), $list_day);
			$calendar.= '</td>';
			if ($running_day == 7) {
				$calendar.= '</tr>';
				if (($day_counter+1) != $days_in_month) $calendar.= '<tr class="calendar-row">';
				$running_day = 0;
				$days_in_this_week = 0;
			}
			$days_in_this_week++; $running_day++; $day_counter++;
		}
		/* finish the rest of the days in the week */
		if ($days_in_this_week != 1 and $days_in_this_week < 8) for ($x = 1; $x <= (8 - $days_in_this_week); $x++) $calendar.= '<td class="calendar-day-np"> </td>';
	
		/* final row */
		$calendar.= '</tr>';
	
		/* end the table */
		$calendar.= '</table></div>';
		
		/* all done, return result */
		return $calendar;
	}
	
	public function sprintUpcoming($events) {
		global $model;
		$content = '';
		if ($events) foreach($events as $event) {
			if ($event['days']) $date = sprintf('<span class="weekly">%s</span>', $model->lang($event['number_days'] > 1 ? 'WEEKLY' : 'WEEKLY_DAY_' . $event['days'][0], 'CalendarModel'));
			else {
				$date = sprintf(
					"<span class=\"date circleDate\" itemprop=\"startDate\" content=\"%s\">
						<span class=\"day\">%d</span><br>
						<span class=\"month\">%s</span><br>
						<span class=\"weekday\">%s</span><br>
					</span>",
					$event['iso_date'],
					date('j', $event['datetime']),
					date('F', $event['datetime']),
					date('D', $event['datetime'])
				);
				if ($event['end_date']) $date .= sprintf(
					"<span class=\"endDate circleDate\" itemprop=\"endDate\" content=\"%s\">
						<span class=\"day\">%d</span><br>
						<span class=\"month\">%s</span><br>
						<span class=\"weekday\">%s</span><br>
					</span>",
					$event['iso_end_date'],
					date('j', $event['datetime_end']),
					date('F', $event['datetime_end']),
					date('D', $event['datetime_end'])
				);
				
			}
			$content .= sprintf("
				<article itemscope itemtype=\"http://schema.org/Event\"><a itemprop=\"url\" href=\"%s\">
					<header>
						<h1 itemprop=\"name\">%s</h1>
						<h2 itemprop=\"location\" itemscope itemtype=\"http://schema.org/Place\" class=\"venue\">
							<meta itemprop=\"url\" content=\"%s\">
							<span itemprop=\"name\">%s</span>
						</h2>
					</header>
					<img alt=\"poster\" itemprop=\"image\" width=\"250\" height=\"250\" src=\"%s\">
					%s
				</a></article>
			",
				$event['url'],
				$event['title'],
				$event['listing_url'],
				$event['venue'],
				$this->getImage($event['calendar_id'], 250, 250),
				$date
			);
		}
		return $content;
	}
	
	public function getDate() {
		if (!$this->date) {
			global $model;
			$date = request($model->args['id']);
			if (!$date or !$date = strtotime($date)) $date = time();
			$this->date = $date;
		}
		return $this->date;
	}
	
	public function getSidebar($activeTab = 'sidebar_events') {
		global $model;
		$date = request($model->args['date']);
		if (!$date or !$date = strtotime($date)) $date = mktime(0,0,0);
		$content = '';
		
		$json = false;
		
		if (!$json) {
			$next = $date + (3600 * 24);
			$prev = $date - (3600 * 24);
			
			$url = $model->url(false, false, true);
			
			ob_start();
			$tabs = array(
				'sidebar_events' => $model->lang('MENU_CALENDAR_EVENTS'),
				'sidebar_specials' => $model->lang('MENU_CALENDAR_SPECIALS'),
				'sidebar_upcoming' => $model->lang('MENU_CALENDAR_UPCOMING')
			);
	?>
		<div class="dateControls">
			<a class="prev" rel="nofollow" data-date="<?=date('c', $prev);?>" title="<?=date('l M j, Y', $prev);?>" href="<?=$model->url(array('date' => date('Y-m-d', $prev)), false, true);?>"><span class="icon icon-arrow-left-2"><span><?=$model->lang('PREV')?></span></span></a>
			<h2><a title="<?=$model->lang('OPEN_CALENDAR', 'CalendarModel', false, true);?>" href="<?=$model->url(array('m' => 'calendar', 'view' => 'date', 'id' => date('Y-m-d', $date)));?>">
				<?=date('l M j, Y', $date);?>
			</a></h2>
			<a class="next" rel="nofollow" data-date="<?=date('c', $next);?>" title="<?=date('l M j, Y', $next);?>" href="<?=$model->url(array('date' => date('Y-m-d', $next)), false, true);?>"><span class="icon icon-arrow-right-2"><span><?=$model->lang('NEXT')?></span></span></a>
		</div>
		<ul class="tabCaptions">
	<? foreach($tabs as $hash => $lang) {
			$class = $hash == $activeTab ? ' class="active"' : '';
			printf('<li><a%s href="%s#%s">%s</a></li>', $class, $url, $hash, $lang);
	} ?>
		</ul>
	<?
			$content = ob_get_clean();
		}
		
		$view = new View;
		if (!$day = $view->setPath('sidebar/calendar.html', true, 300, $date)) {
			$view->setTag('date', $date);
			$view->setTag('active', $activeTab);
			$events = $this->getEvents($date, true, true);
			$view->setTag('upcoming', $this->sprintEvents($this->getUpcoming($date), 'upcoming', false, 60, 60, 2));
			
			$view->setTag('events', $this->sprintEvents($events['NULL'], 'date', false, 60, 60, 2));
			$view->setTag('specials', $this->sprintEvents($events['specials'], 'date', false, 60, 60, 2));
			$day = $view->getOutput();
		}
		return $content . $day;
	}
	
	public function getImageByID($id) {
		$exts = array('jpg', 'png', 'gif');
		foreach ($exts as $ext) {
			$path = EVENTS_POSTER_STORE_FILEPATH . 'event_' . $id . '.' . $ext;
			if (file_exists($path)) return $path;
		}
		return false;
	}
	public function removePoster($id) {
		$name = $this->getImageByID($id);
		$GLOBALS['model']->tool('image')->clearCache($name);
		return unlink($name);
	}
	
	public function getFullPoster($id) {
		return $this->getImage($id, 800, 1000, false, true);
	}
	
	public function getImage($id, $width = false, $height = false, $noPlaceholder = false, $noCrop = false) {
		$path = $this->getImageByID($id);
		if (!$path or !file_exists($path)) {
			if ($noPlaceholder) return false;
			$path = $GLOBALS['rootPath'] . '/assets/logo/calendar-bg.png';
		}
		if ($path and ($width or $height)) {
			$path = $GLOBALS['model']->tool('image')->resize($path, $width, $height, false, !$noCrop);
		}
		return str_replace($GLOBALS['rootPath'], '', $path);
	}
	
	// $realImageSize can be adjusted for retina display, to make them fit higher res screens
	public function sprintEvents($events, $view = 'date', $articleClass = 'span4', $imageWidth = 150, $imageHeight = 150, $realImageSize = 1) {
		global $model;
		$content = '';
		if (is_array($events)) {
			if ($view == 'multiple-days') foreach($events as $day => $_events) {
				$date = strtotime($day);
				$content .= sprintf('<h1 class="eventDate">%s</h1>', $date > 0 ? date('l, M j, Y', $date) : $model->lang('WEEKLY', 'CalendarModel'));
				$content .= $this->sprintEvents($_events, 'date', $articleClass, $imageWidth, $imageHeight, $realImageSize);
			} else foreach($events as $event) {
				$content .= $this->sprintEvent($event, $view, $articleClass, $imageWidth, $imageHeight, $realImageSize);
			}
		}
		return $content;
	}
	
	public function sprintEvent($event, $view = 'date', $articleClass = 'span4', $imageWidth = 150, $imageHeight = 150, $realImageSize = 1) {
		global $model, $user;
		if (!request($event['_processed'])) $event = $this->processEvent($event);
		if ($image = $this->getImage($event['calendar_id'], $imageWidth * $realImageSize, $imageHeight * $realImageSize)) {
			$img = sprintf('<img alt="poster" width="%d" height="%d" src="%s"><meta itemprop="image" content="%s">', $imageWidth, $imageHeight, $image, $this->getFullPoster($event['calendar_id']));
		}
		
		$price = $event['price_formatted'] ?
			sprintf('<span class="price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
				<meta itemprop="priceCurrency" content="CNY">
				<span itemprop="price">%s</span> %s
			</span>', $event['price_formatted'], $event['currency']) : '';
		
		$printTime = $view == 'date' ? $event['starting_time_formatted'] : $event['date_formatted'];
		if ($event['datetime']) {
			$time = $printTime ?
				sprintf(
					'<span class="date" itemprop="startDate" content="%s">%s</span>',
					$event['iso_date'],
					$printTime
				) : '';
			if ($event['datetime_end']) $time .= sprintf('<meta itemprop="endDate" content="%s">', $event['iso_end_date']);
		}
		
		return sprintf("
			<article class=\"event %s\" itemscope itemtype=\"http://schema.org/Event\">
				<a itemprop=\"url\" href=\"%s\">
					%s
					%s
					<span class=\"top\">
						%s
						<span itemprop=\"location\" itemscope itemtype=\"http://schema.org/Place\" class=\"venue\">
							<meta itemprop=\"url\" content=\"%s\">
							<span itemprop=\"name\">%s</span>
						</span>
					</span>
					%s
					<h1 itemprop=\"name\">%s</h1>
					%s
				</a>
			</article>
		",
			$articleClass,
			$event['url'],
			$img,
			$event['status'],
			$time,
			$event['listing_url'],
			$event['venue'],
			$event['category_formatted'],
			$event['title'],
			$price
		);
	}
}
?>