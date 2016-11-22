<?php
class DateTimeControl {

	private $year_type = 'input';
	private $unix_datetime;
	private $year_select_years = 3;
	private $minutes_interval = 5;
	private $display_time = true;
	private $blank_year = false;

	public function __construct($unix_datetime = '', $add_hours = 0) {
		if ($unix_datetime == '') {
			$this->unix_datetime = time() + $add_hours*3600;
		}
		else {
			$this->unix_datetime = $unix_datetime + $add_hours*3600;
		}

		$this->year_start = date('Y', $this->unix_datetime);
		// needs a little thought here - i think it really needs to begin in the current year
		// and then run until the $year_select_years have been covered OR the in-use year has been covered
	}

	public function display() {
		if ($this->date_label != '')
			$output .= $this->date_label;

		// years
		if ($this->year_type == 'input') {
			if ($this->blank_year)
				$this->year_start = '';

			$output .= "<input name=\"{$this->prefix}yyyy\" type=\"text\" class=\"year\" size=\"4\" maxlength=\"4\" value=\"$this->year_start\">";
		}
		else {
		$output .= "<select id=\"{$this->prefix}yyyy\" name=\"{$this->prefix}yyyy\"".$this->getOnChange().">";

			for ($i = 0; $i < $this->year_select_years; $i++)
			{
			$yyyy = $this->year_start + $i;
			$output .= "<option value=\"$yyyy\"".($yyyy == date('Y', $this->unix_datetime) ? ' selected' : '').">$yyyy</option>\n";
			}

		$output .= "</select>";
		}

	// months
	$output .= "<select id=\"{$this->prefix}mm\" name=\"{$this->prefix}mm\"".$this->getOnChange().">";

		for ($i = 1; $i <= 12; $i++)
		{
		$mm = str_pad($i, 2, '0', STR_PAD_LEFT);
		$output .= "<option value=\"$mm\"".($mm == date('m', $this->unix_datetime) ? ' selected' : '').">$mm</option>\n";
		}

	$output .= "</select>";

	// days
	$output .= "<select id=\"{$this->prefix}dd\" name=\"{$this->prefix}dd\"".$this->getOnChange().">";

		for ($i = 1; $i <= 31; $i++)
		{
		$dd = str_pad($i, 2, '0', STR_PAD_LEFT);
		$output .= "<option value=\"$dd\"".($dd == date('d', $this->unix_datetime) ? ' selected' : '').">$dd</option>\n";
		}

	$output .= "</select>";

	if ($this->display_time) {
		// space to separate date and time
		$output .= "&nbsp;";

			if ($this->time_label != '')
			{
			$output .= $this->time_label;
			}

		// hours
		$output .= "<select name=\"{$this->prefix}hh\">";

			for ($i=0; $i<=23; $i++)
			{
			$hh = str_pad($i, 2, '0', STR_PAD_LEFT);
			$output .= "<option value=\"$hh\"".($hh == date('H', $this->unix_datetime) ? ' selected' : '').">$hh</option>\n";
			}

		$output .= '</select>';

		// minutes
		$output .= "<select name=\"{$this->prefix}min\">";

		for ($i=0; $i<=59; $i += $this->minutes_interval) {
			$min = str_pad($i, 2, '0', STR_PAD_LEFT);
			$output .= "<option value=\"$min\"".($min == date('i', $this->unix_datetime) - date('i', $this->unix_datetime) % $this->minutes_interval ? ' selected' : '').">$min</option>\n";
		}

		$output .= '</select>';
	}

	$output .= " <div id=\"date_text\" style=\"display: inline\"></div>";
	return $output;
	}

	function setMinutesInterval($interval)
	{
	$this->minutes_interval = $interval;
	}

	public function setYearType($type) {
		if ($type == 'select') {
			$this->year_type = 'select';
			// need to look here at start and end values
		}
		// else leave as default: 'input'
	}

	function setPrefix($prefix)
	{
	$this->prefix = $prefix.'_';
	}

	function setDateLabel($label)
	{
	$this->date_label = $label;
	}

	function setTimeLabel($label)
	{
	$this->time_label = $label;
	}

	function disableTime()
	{
	$this->display_time = false;
	}

	public function blankYear() {
		$this->blank_year = true;
	}

	public function setOnChange($onchange) {
		$this->onchange = $onchange;
	}

	private function getOnChange() {
		if ($this->onchange)
			return " onchange=\"$this->onchange\"";
	}


/* USAGE

	$dt_control = new DateTimeControl($this->ts_unix);
	$dt_control->setYearType('select');
	$dt_control->setPrefix('posted');
	$dt_control->setDateLabel('Date: ');
	$dt_control->setTimeLabel('Time: ');
	$dt_control->display();

*/
}