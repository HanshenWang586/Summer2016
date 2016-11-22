<?
class DatetimeTools extends CMS_Class {
	private $data = array();
	
	public function init($args) {
	
	}
	
	public function getMonths($short = false, $noAssoc = false) {
		return $this->getData('month', 12, $short, $noAssoc);
	}
	
	public function getDays($short = false, $noAssoc = false) {
		return $this->getData('day', 7, $short, $noAssoc);
	}
	
	public function getDay($index, $short = false) {
		return array_get($this->getDays($short), $index);
	}
	
	public function getMonth($index, $short = false) {
		return array_get($this->getMonths($short), $index);
	}
	
	private function getData($type, $end, $short = false, $noAssoc = false) {
		if ($short) $type = $type . '_' . 'SHORT';
		if (array_key_exists($type, $this->data)) {
			return $noAssoc ? array_values($this->data[$type]) : $this->data[$type];
		}
		$end++;
		$data = array();
		$prefix = strtoupper($type) . '_';
		for ($i = 1; $i < $end; $i++) {
			$data[$i] = $this->lang($prefix . $i, false, false, true);
		}
		return $this->data[$type] = $data;
	}
	
	public function getYears($startYear, $endYear = false) {
		if (!$endYear) $endYear = date('Y');
		$years = array();
		for ($i = $startYear; $i <= $endYear; $i++) {
			$years[] = $i;
		}
		return $years;
	}
	
	public function getDateTag($unixTime, $class = false, $itemprop = false, $showTime = false) {
		$format = $showTime ? 'F j, Y, g:ia' : 'F j, Y';
		if (is_string($unixTime) && !is_numeric($unixTime)) $unixTime = strtotime($unixTime);
		$date_c = date('c', $unixTime);
		return sprintf('<time%s%s content="%s" datetime="%s">%s</time>', $class ? sprintf(' class="%s"', $class) : '', $itemprop ? sprintf(' itemprop="%s"', $itemprop) : '', $date_c, $date_c, date($format, $unixTime));
	}
}

?>