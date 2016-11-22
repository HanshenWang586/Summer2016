<?php
class CalendarItem {
	public function __construct($calendar_id = '') {
		if (ctype_digit($calendar_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
										UNIX_TIMESTAMP(event_date) AS ts_unix
								FROM calendar_events e
								LEFT JOIN listings_data l ON (l.listing_id = e.listing_id)
								WHERE calendar_id = '.$calendar_id);
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}

	function setDate($date = '') {
		if ($date != '') {
			list($date_year, $date_month, $date_day) = explode('-', $date);
			$this->ts_unix = mktime(0,0,0,$date_month, $date_day, $date_year);
		}
	}

	public function displayForm($duplicate = false) {
		global $admin_user;

		$dt_control = new DateTimeControl($this->ts_unix);
		$dt_control->setYearType('select');
		$dt_control->disableTime();
		$dt_control->setOnChange("showDay();seekEvents();");

		$content .= FormHelper::open('form_item_proc.php', array('id' => 'form_calendar_item'));

		if (!$duplicate)
			$content .= FormHelper::hidden('calendar_id', $this->calendar_id);
		
		$content .= FormHelper::fieldset('Site', $f);
		
		$location = "<div id=\"selected_location\">";

		if ($this->listing_id) {
			$listing = new ListingsItem($this->listing_id);
			$location .= $listing->getCalendarFormSummary();
		}
		else {
			$location .= "<input id=\"location\" onkeyup=\"calendarSuggestLocations()\"><br />
			<div id=\"suggested_locations_loading\">loading...</div>
			<div id=\"suggested_locations\" style=\"margin-left:130px;\"></div>";
		}

		$location .= '</div>';
				
		$f[] = FormHelper::element('Location', $location);
		$f[] = FormHelper::element('Date', $dt_control->display());
		$guidetext = "To add a link do this: #text#link#<br />e.g. #Google#http://www.google.com#<br />This example
		will appear like <a href=\"http://www.google.com\" target=\"_blank\" class=\"underline\">Google</a>. It's important
		that you remember the 'http://' part.";
		$f[] = FormHelper::textarea('Description', 'description', $this->description, array('guidetext' => $guidetext));
		$content .= FormHelper::fieldset('Event', $f);
		
		$content .= FormHelper::element('', '<div id="existing_events"></div>');
		$content .= FormHelper::submit('Save', array('id' => 'form_calendar_item_submit'));
		$content .= FormHelper::close();

		if ($this->listing_id)
			$content .= "<script>showDiv('form_calendar_item_submit');</script>";

		return $content;
	}

	public function getPublic() {
		global $site;

		$li = new ListingsItem($this->listing_id);
		$content .= "<a class=\"venue\" href=\"".$li->getURL()."\">".$li->getName().'</a>';
		if ($li->getCityID() != $site->getHomeCityID()) {
			$city = new Place($li->getCityID());
			$content .= ', '.$city->getLink();
		}
		$event = ContentCleaner::linkHashURLs($this->description);
		$event = ContentCleaner::wrapChinese($event);
		$content .= ContentCleaner::PWrap($date.$event);
		return $content;
	}

	public function save() {
		$db = new DatabaseQuery;
		$this->description = ContentCleaner::cleanForDatabase($this->description);

		if (!ctype_digit($this->calendar_id)) {
			$db->execute("	INSERT INTO calendar_events (	listing_id,
															description,
															event_date)
							VALUES (	$this->listing_id,
										'".$db->clean($this->description)."',
										'$this->yyyy-$this->mm-$this->dd')");
		}
		else {
			$db->execute("	UPDATE calendar_events
							SET listing_id = $this->listing_id,
								description = '".$db->clean($this->description)."',
								event_date = '$this->yyyy-$this->mm-$this->dd'
							WHERE calendar_id = $this->calendar_id");
		}
		
		$li = new ListingsItem($this->listing_id);
		$li->squash();
	}

	function displayAdminRow()
	{
	$content = "<tr valign=\"top\">
				<td>$this->name_en</td>
				<td width=\"300\">$this->e_description</td>
				<td>$this->event_date</td>
				<td><a href=\"form_item.php?calendar_id=$this->calendar_id\">Edit</a></td>
				<td><a href=\"delete_item.php?calendar_id=$this->calendar_id\" onClick=\"return conf_del()\">Delete</a></td>
				</tr>";
	return $content;
	}

	function delete()
	{
	$db = new DatabaseQuery;
	$db->execute("	UPDATE calendar_events
					SET live=0
					WHERE calendar_id=$this->calendar_id");
	}

	public function getDate() {
		return $this->yyyy.'-'.$this->mm.'-'.$this->dd;
	}
}
?>