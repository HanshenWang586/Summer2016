 <?php
class CalendarController {

	public function index() {
		global $user, $model;

		$p = new Page();
		
		$view = new View('events/list.html');
		$title = $GLOBALS['model']->lang('EVENTS_PAGE_TITLE', 'CalendarModel');
		
		$cal = new Calendar;
		$date = $cal->getDate();
			
		$path = 'calendar/' . $date;
		$content = $user->isLoggedIn() ? '' : $model->tool('cache')->get($path);
		
		if (!$content) {
			$view->setTag('isAdmin', $user->getPower() == 1);
			
			$view->setTag('date', $date);
			$view->setTag('calendar', $cal->printThreeMonths());
			
			$ads = $model->module('ads');
			if ($ad = $ads->get('topCalendar')) $view->setTag('topAd', sprintf('<div class="promCalendar" id="promCalendarTop">%s</div>', $ad));
			
			$view->setTag('upcoming', $cal->sprintUpcoming($cal->getUpcoming($date)));
			
			$events = $cal->getEvents($date);
			$view->setTag('current', $cal->sprintEvents($events['NULL'], 'date'));
			$view->setTag('recurring', $cal->sprintEvents($events['specials'], 'date'));
			
			$view->setTag('title', $title);
			
			$content = $view->getOutput();
			
			if (!$user->isLoggedIn()) $model->tool('cache')->set($path, $content, 300);
		}
		$p->setTag('page_title', $title);
		$p->setTag('main', $content);
		$p->output();
	}
	
	public function moderate() {
		global $user, $model;
		
		if (!$user->getPower()) HTTP::disallowed();
		
		$p = new Page();
		
		$view = new View('events/list.html');
		$title = $GLOBALS['model']->lang('EVENTS_APPROVE_TITLE', 'CalendarModel');
		
		$cal = new Calendar;
			
		$bl = new BlogList;
		
		$view->setTag('isAdmin', $user->getPower() == 1);
		
		$view->setTag('calendar', $cal->printThreeMonths());
		
		$events = $cal->getApproveList();
		
		$view->setTag('current', $cal->sprintEvents($events, 'multiple-days'));
		
		$view->setTag('title', $title);
		
		$content = $view->getOutput();
		
		$p->setTag('page_title', $title);
		$p->setTag('main', $content);
		$p->output();
	}
	
	public function approve($id = false) {
		global $model, $user;
		
		if (!$user->getPower()) HTTP::disallowed();
		$cal = new Calendar;
		if (!$id or !is_numeric($id) or !$cal->eventExists($id)) HTTP::throw404();
		
		$model->db()->update('calendar_events', array('calendar_id' => $id), array('approved' => 1));
		
		HTTP::redirect($cal->getEventURL($id));
	}
	
	public function reject($id = false) {
		global $model, $user;
		
		if (!$user->getPower()) HTTP::disallowed();
		$cal = new Calendar;
		if (!$id or !is_numeric($id) or !$cal->eventExists($id)) HTTP::throw404();
		
		$model->db()->update('calendar_events', array('calendar_id' => $id), array('approved' => -1));
		
		HTTP::redirect($cal->getEventURL($id));
	}
	
	public function delete($id = false) {
		global $model, $user;
		
		$cal = new Calendar;
		if (!$id or !is_numeric($id) or !$cal->eventExists($id)) HTTP::throw404();
		
		if (!$user->getPower() and !$this->isOwner($id)) HTTP::disallowed();
		
		$model->db()->update('calendar_events', array('calendar_id' => $id), array('live' => 0));
		
		HTTP::redirect($cal->getEventURL($id));
	}
	
	public function undelete($id = false) {
		global $model, $user;
		
		$cal = new Calendar;
		if (!$id or !is_numeric($id) or !$cal->eventExists($id)) HTTP::throw404();
		
		if (!$user->getPower() and !$this->isOwner($id)) HTTP::disallowed();
		
		$model->db()->update('calendar_events', array('calendar_id' => $id), array('live' => 1));
		
		HTTP::redirect($cal->getEventURL($id));
	}
	
	private function isOwner($event_id, $user_id = false) {
		global $model, $user;
		
		if (!$user_id) if (!$user->isLoggedIn() or !$user_id = $user->getUserID()) return false;
		
		return false != $model->db()->query('calendar_events', array('calendar_id' => $event_id, 'user_created' => $user_id), array('selectField' => 'calendar_id'));
	}
	
	public function locations() {
		$id = request($_POST['location_id']);
		if (!$id or !is_numeric($id)) die('bad request');
		$listing = new ListingsItem($id);
		echo $listing->getCalendarFormSummary();
		die();
	}
	
	public function findlocations() {
		$links = array();
		
		$term = request($_POST['location_stub']);
		if (!$term) die('bad request');
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM listings_data d, listings_cities c
							WHERE name_en LIKE '%".$db->clean($term)."%'
							AND status = 1
							AND d.city_id = c.city_id
							ORDER BY name_en");
		
		if ($rs->getNum()) {
			while ($row = $rs->getRow())
				$links[] = "<a href=\"javascript:void(null)\" onClick=\"calendarUseSuggestedLocation({$row['listing_id']});\">{$row['name_en']} ({$row['city_en']}: {$row['address_en']})</a>";
	
			echo HTMLHelper::wrapArrayInUl($links);
		}
		else
			echo 'No results found. Perhaps the venue isn\'t in the database?';
		die();
	}
	
	public function suggest() {
		echo "<input class=\"text\" id=\"location\" onkeyup=\"calendarSuggestLocations()\">
			<div id=\"suggested_locations\"></div>
			<div id=\"suggested_locations_loading\">loading...</div>";
	}
	
	public function date() {
		$this->index();
	}
	
	public function event($event_id = false) {
		global $user, $model, $site;
		
		if (!$event_id or !is_numeric($event_id)) HTTP::Throw404();
		
		$p = new Page();
		
		$view = new View('events/event.html');
		
		$cal = new Calendar;
		$event = $cal->getEvent($event_id);
		if (!$event) HTTP::Throw404();
		
		$qr = new QR;
		$view->setTag('qrcode', $qr->generate($event['url']));
		
		$view->setTag('isAdmin', $user->getPower() == 1);
		$view->setTag('isOwner', $event['user_created'] === $user->getUserID());
		
		$view->setTag('userAdded', $site->getUser($event['user_created']));
	
		$view->setTag('timeAdded', $model->tool('Datetime')->getDateTag($event['created']));
		$view->setTag('userUpdated', $site->getUser($event['user_updated']));
		$view->setTag('timeUpdated', $model->tool('Datetime')->getDateTag($event['updated']));
		
		$view->setTag('event', $event);
		
		$thumb = $cal->getImage($event['calendar_id'], 295, false, true);
		if ($thumb) {
			$view->setTag('poster_thumb', $thumb);
			$view->setTag('poster', $cal->getFullPoster($event['calendar_id']));
			if ($img = $cal->getImage($event['calendar_id'])) {
				$img = $GLOBALS['rootURL'] . $img;
				$site->addMeta('og:image', $img, 'property');
			}
		}
		
		$social = new Social;
		$social_name = $event['title'] . ' @ ' . $event['venue'] . ' – ' . $model->lang('CALENDAR', false, false, true);
		$view->setTag('social', $social->getSharingList($social_name, $img));
		$view->setTag('title', $GLOBALS['model']->lang('EVENTS_PAGE_TITLE', 'CalendarModel'));
			
		$short = 'GoKunming Events Calendar – Events for Kunming and Yunnan province, China';
		
		$p->setTag('page_title', $social_name);
		$site->addMeta('og:description', $short, 'property');
		$site->addMeta('description', $short);
		$site->addMeta('og:type', 'event', 'property');
		$site->addMeta('event:starting_time', date('c', $event['datetime']), 'property');
		$site->addMeta('og:url', $event['url'], 'property');
		
		
		$view->setTag('related', $cal->sprintUpcoming($cal->getRelated($event['calendar_id'], $event['listing_id'])));
		
		if ($event['latitude'] > 0) {
			$view->setTag('map', sprintf(
				'<div class="google_map" data-zoom="16" data-longitude="%s" data-latitude="%s">
					<div class="infoHeader">
						<span class="icon icon-info"> </span>
						<span class="infoText">
							<a href="%s" class="listingTitle">%s</a> • %s
						</span>
					</div>
					<div class="mapContainer"></div>
				</div>',
				$event['longitude'],
				$event['latitude'],
				$event['listing_url'],
				$event['venue'],
				$event['address']
			));
		}
		
		$content = $view->getOutput();
		
		$p->setTag('main', $content);
		$p->output();
	}
	
	public function poster($id = false) {
		global $user, $model;
		
		if (!$id or !is_numeric($id)) HTTP::Throw404();
		$cal = new Calendar;
		if (!$cal->eventExists($id)) HTTP::Throw404();
		
		// If the user is not authorized, get out of here
		if (!$user->isLoggedIn() or (
			!$user->getPower() and
			($id and $model->db()->query('calendar_events', array('calendar_id' => $id), array('selectField' => 'user_created')) != $user->getUserID())
		)) HTTP::disallowed();
		
		$uploader = $model->tool('uploader');
		
		$poster = $cal->getImageByID($id);
		
		if ($uploader->exists('file')) {
			$o = array();
			$uploader->setUploadFolder(EVENTS_POSTER_STORE_FILEPATH);
			if ($uploader->captureUpload('file')) {
				$file = $uploader->successful[0]['target'];
				if ($poster) $cal->removePoster($id);
				$ext = $uploader->successful[0]['extension'];
				if ($ext = 'jpeg') $ext = 'jpg';
				$target = EVENTS_POSTER_STORE_FILEPATH . 'event_' . $id . '.' . $ext;
				if (rename($file, $target) and $new = $model->tool('image')->resize($target, 1000, 800, true)) {
					HTTP::redirect($cal->getEventURL($id));
				}
			}
		}
		
		$p = new Page();
		
		$title = $model->lang('EDIT_POSTER', 'CalendarModel', false, true);
		$event = $cal->getEvent($id);
		$content = sprintf("<h1 class=\"dark\">%s</h1><section id=\"event\" class=\"row\"><h1><a href=\"%s\">%s</a></h1>", $title, $event['url'], $event['title']);
		
		if ($poster) {
			$thumb = $cal->getImage($id, 295, false, true);
			$info = getimagesize($poster);
			$content .= sprintf('
				<div class="span3 pull-right">
					<img class="poster" src="%s" width="295">
					<div class="whiteBox pull-right">
						<small>%s</small>
						<p>%s: %dx%dpx</p>
					</div>
				</div>
			',
				$thumb,
				$model->lang('IMAGE_INFO', 'CalendarModel'),
				$model->lang('DIMENSIONS', 'CalendarModel'),
				$info[0],
				$info[1]
			);
		}
		$content .= sprintf(
			"<div class=\"span5\"><form action=\"%s\" method=\"post\" enctype=\"multipart/form-data\">
				<fieldset>
					<legend>%s</legend>
					<p>%s: %s</p>
					<label>%s</label><input type=\"file\" name=\"file\">
					<input class=\"submit\" type=\"submit\" value=\"%s\">
				</fieldset>
			</form></div>",
			$model->url(false, false, true),
			$model->lang('UPLOAD_POSTER_FORM', 'CalendarModel'),
			$model->lang('MAX_UPLOAD_SIZE', 'CalendarModel'),
			formatSize($uploader->getMaxUploadSize()),
			$model->lang('FORM_POSTER_UPLOAD_CAPTION', 'CalendarModel'),
			$model->lang('FORM_SAVE_POSTER', 'CalendarModel', false, true)
		);
		
		$content .= '</section>';
		$p->setTag('page_title', $title);
		$p->setTag('main', $content);
		$p->output();
	}
	
	private function edit_event($pageTitle, $id = false) {
		global $user, $model;
		
		// If the user is not authorized, get out of here
		if (!$user->isLoggedIn() or (
			!$user->getPower() and
			($id and $model->db()->query('calendar_events', array('calendar_id' => $id), array('selectField' => 'user_created')) != $user->getUserID())
		)) HTTP::disallowed();
		
		$cal = new Calendar;
		
		if (!empty($_POST)) {
			$values = array('listing_id', 'description', 'category', 'title', 'price', 'all_day', 'live');
			$event = array();
			
			$form = new EventsEditForm(array('types' => $cal->getTypes(), 'categories' => $cal->getCategories(), 'event_id' => $id));
			$form->setData($_POST);
			$ev = new ExistenceValidator($form);
			$ev->validate('listing_id', $model->lang('E_FORM_NO_LISTING', 'CalendarModel'));
			$ev->validate('description', $model->lang('E_FORM_NO_DESCR', 'CalendarModel'));
			$ev->validate('type', $model->lang('E_FORM_NO_TYPE', 'CalendarModel'));
			$ev->validate('category', $model->lang('E_FORM_NO_CATEGORY', 'CalendarModel'));
			if (!in_array($form->getDatum('type'), array_keys($cal->types))) $form->addError($model->lang('E_FORM_WRONG_TYPE', 'CalendarModel'));
			if (!in_array($form->getDatum('category'), array_keys($cal->categories))) $form->addError($model->lang('E_FORM_WRONG_CATEGORY', 'CalendarModel'));
			
			if (!($form->getDatum('price') > -2)) $form->addError($model->lang('E_FORM_BAD_PRICE', 'CalendarModel'));
			
			$lv = new LengthValidator($form);
			$lv->setMinLength(10);
			$lv->setMaxLength(50);
			$lv->validate('title', $model->lang('E_FORM_TITLE_10_50', 'CalendarModel'));
			
			if (!$form->getDatum('all_day')) {
				$ev->validate('starting_time', $model->lang('E_FORM_NO_STARTING_TIME', 'CalendarModel'));
				$values[] = 'starting_time';
			} else $event['starting_time'] = NULL;
			
			$type = $form->getDatum('type');
			if ($type == 'one-day' or $type == 'multiple-days') {
				$event['days'] = NULL;
				$values[] = 'event_date';
				if ($type == 'one-day' or strtotime($form->getDatum('end_date')) < time()) {
					if (strtotime($form->getDatum('event_date')) < strtotime(date('Y-m-d'))) {
						$form->addError($model->lang('E_FORM_DATE_CANNOT_BE_PAST', 'CalendarModel'));
					}
					if (!$form->getDatum('all_day') and (strtotime($form->getDatum('event_date') . ' ' . $form->getDatum('starting_time')) + 3600) < time()) {
						$form->addError($model->lang('E_FORM_TIME_CANNOT_BE_OVER_1_HOUR_PAST', 'CalendarModel'));
					}
				}
			}
			if ($type == 'one-day') {
				$event['end_date'] = NULL;
			}
			if ($type == 'multiple-days') {
				$values[] = 'end_date';
				if (strtotime($form->getDatum('event_date')) >= strtotime($form->getDatum('end_date'))) {
					$form->addError($model->lang('E_FORM_END_DATE_SMALLER_THAN_START_DATE', 'CalendarModel'));
				}
			}
			if ($type == 'weekly') {
				$event['event_date'] = NULL;
				$event['end_date'] = NULL;
				$values[] = 'days';
				$days = $form->getDatum('days');
				if (!$days or !is_array($days)) $form->addError($model->lang('E_FORM_NO_DAYS', 'CalendarModel'));
				else $_POST['days'] = implode(',',$days);
			}
			$event['group'] = $form->getDatum('specials') ? 'specials' : NULL;
			if (!$form->getErrorCount()) {
				$event = array_select_keys($values, $_POST, false, $event);
				$event['approved'] = $user->getPower() ? (int) $form->getDatum('approved') : 0;
				$event['sidebar'] = $user->getPower() ? (int) $form->getDatum('sidebar') : 0;
				$event['user_updated'] = $user->getUserID();
				$event['updated'] = unixToDatetime();
				if ($id) {
					$result = $model->db()->update('calendar_events', array('calendar_id' => $id), $event);
					if ($result >= 0) HTTP::redirect($cal->getEventURL($id, $event['title']));
					else {
						$form->addError($model->lang('E_FORM_DB_ERROR', 'CalendarModel'));
						//$form->addError($model->db()->getError() . ' - ' . $model->db()->getQuery());
					}
				} else {
					$event['user_created'] = $event['user_updated'];
					$event['created'] = $event['updated'];
					$result = $model->db()->insert('calendar_events', $event);
					if ($result) HTTP::redirect($cal->getEventURL($result, $event['title']));
					else {
						$form->addError($model->lang('E_FORM_DB_ERROR', 'CalendarModel'));
						//$form->addError($model->db()->getError() . ' - ' . $model->db()->getQuery());
					}
				}
			}
		} else {
			$form = isset($_SESSION['eventsEditForm']) ? $_SESSION['eventsEditForm'] : new EventsEditForm(array('types' => $cal->getTypes(), 'categories' => $cal->getCategories(), 'event_id' => $id));
			if ($id) {
				$event = $model->db()->query('calendar_events', array('calendar_id' => $id), array('singleResult' => true));
				$event['type'] = $event['days'] ? 'weekly' : ($event['end_date'] ? 'multiple-days' : 'one-day');
				$form->setData($event);
			}
		}
		
		$p = new Page;
		
		$body .= sprintf('<h1 class="dark">%s</h1>', $pageTitle);
		$p->setTag('scripts', '<script src="/js/modules/calendarform.js?2"></script>');
		
		if ($id) $body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $cal->getEventURL($id, $event['title']), $model->lang('BACK_TO_EVENT', 'CalendarModel'));
		
		$body .= $form->display();
	
		unset($_SESSION['add_listing_form']);
		
		$p->setTag('page_title', $pageTitle);
		$p->setTag('main', $body);
		$p->output();
	}

	public function edit($id = false) {
		if (!$id or !is_numeric($id)) HTTP::throw404();
		
		$this->edit_event($GLOBALS['model']->lang('EDIT_EVENT_TITLE', 'CalendarModel'), $id);
	}
	
	public function post() {
		$this->edit_event($GLOBALS['model']->lang('POST_EVENT_TITLE', 'CalendarModel'));
	}
}
?>