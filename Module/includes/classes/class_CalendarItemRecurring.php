<?php
class CalendarItemRecurring {

	private $days = array(	1 => 'Monday',
							2 => 'Tuesday',
							3 => 'Wednesday',
							4 => 'Thursday',
							5 => 'Friday',
							6 => 'Saturday',
							7 => 'Sunday');
	private $sidebar = 0;

	public function __construct($calendar_id = '') {
		if (ctype_digit($calendar_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *,
										e.description AS description
								FROM calendar_recurring e, listings_data l
								WHERE l.listing_id = e.listing_id
								AND calendar_id = $calendar_id");
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function displayForm($duplicate = false) {
		global $admin_user;
		$content = FormHelper::open('form_recurring_proc.php');
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('calendar_id', $this->calendar_id);

		$f[] = FormHelper::select('Day', 'event_day', $this->days, $this->event_day);
		$f[] = FormHelper::radio('Sidebar', 'sidebar', array(0 => 'Hide', 1 => 'Show'), $this->sidebar, array('guidetext' => 'Setting this to \'Show\' will put this event in the sidebar calendar despite it being a recurring event.'));

		if ($this->listing_id) {
			$listing = new ListingsItem($this->listing_id);
			$f[] = FormHelper::element('Location', $listing->getCalendarFormSummary());
		}
		else {
			$f[] = FormHelper::element('Location', "<div id=\"selected_location\" style=\"margin-left:128px;\"><input id=\"location\" onkeyup=\"calendarSuggestLocations()\"><br />
			<div id=\"suggested_locations_loading\">loading...</div>
			<div id=\"suggested_locations\"></div></div>");
		}

		$f[] = FormHelper::textarea('Description', 'description', $this->description);

		$content .= FormHelper::fieldset('Recurring Event', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		
		return $content;
	}

	public function getPublic($show_venue = false) {
		global $model;

		if (!$show_venue)
			$date = '<strong>'.$this->days[$this->event_day].'s</strong><br />';//'<strong>'.DateManipulator::convertUnixToFriendly($this->event_date_unix, array('show_day' => true)).'</strong><br />';

		if ($show_venue) {
			$li = new ListingsItem($this->listing_id);
			$content .= "<a href=\"".$li->getURL()."\" title=\"Click to view ".$li->getName()." in ".$model->getLang('SITE_NAME')."'s listings\">".$li->getName().'</a>';
		}

		$event = ContentCleaner::linkHashURLs($this->description);
		$event = ContentCleaner::wrapChinese(nl2br($event));
		$content .= ContentCleaner::PWrap($date.$event);
		return $content;
	}

	function save() {
		$db = new DatabaseQuery;
		$this->description = ContentCleaner::cleanForDatabase($this->description);

		if (!ctype_digit($this->calendar_id)) {
			$db->execute("	INSERT INTO calendar_recurring (listing_id,
															description,
															event_day,
															sidebar)
							VALUES (	$this->listing_id,
										'".$db->clean($this->description)."',
										$this->event_day,
										$this->sidebar)");
		}
		else {
			$db->execute("	UPDATE calendar_recurring
							SET listing_id = $this->listing_id,
								description = '".$db->clean($this->description)."',
								event_day = $this->event_day,
								sidebar = $this->sidebar
							WHERE calendar_id = $this->calendar_id");
		}
	}

	public function toggleLive() {
		$db = new DatabaseQuery;
		$db->execute("	UPDATE calendar_recurring
						SET live = (live + 1) % 2
						WHERE calendar_id = $this->calendar_id");
	}

	public function delete() {
		$db = new DatabaseQuery;
		$db->execute("	DELETE FROM calendar_recurring
						WHERE calendar_id = $this->calendar_id");
	}
}
?>