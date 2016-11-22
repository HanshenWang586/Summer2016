<?php
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

$ml = new MiniLog;
$ml->start();
$w = $model->tool('cache')->get('weather');

echo ($w ? "Weather has cache" : "Weather cache empty") . "\n";

if (!$w or !$ut = request($w['cache_refresh_epoch']) or $ut < time()) {
	try {
		if ($new_w = @file_get_contents("http://api.wunderground.com/api/34173c0d1bf46c19/geolookup/forecast/astronomy/conditions/q/zmw:00000.1.WZPPP.json")) {
			$new_w = json_decode($new_w, true);
			if ($new_w and request($new_w['current_observation']) and $new_w['current_observation']['observation_epoch'] > request($w['current_observation']['observation_epoch'])) {
				$w = $new_w;
				$w['sunrise'] = date_create()->setTime($w['moon_phase']['sunrise']['hour'], $w['moon_phase']['sunrise']['minute']);
				$w['sunset'] = date_create()->setTime($w['moon_phase']['sunset']['hour'], $w['moon_phase']['sunset']['minute']);
				$w['city'] = $w['current_observation']['display_location']['full'];
				unset($w['response'], $w['location'], $w['current_observation']['observation_location'], $w['current_observation']['display_location']);
				$cache_refresh_epoch = $w['current_observation']['observation_epoch'] + 4000;
			}
		}
	} catch (Exception $e) {
		
	}
	if (!is_array($w)) $w = array();
	$w['cache_refresh_epoch'] = ifElse($cache_refresh_epoch, time() + 180);
	$model->tool('cache')->set('weather', $w, 3600 * 24);
}

$ml->log('run weather cache	');

$ml->end();

class MiniLog {

	public function start() {
		$this->start = microtime(true);
		$this->last = $this->start;
	}

	public function log($text) {
		echo $text.': '.(microtime(true) - $this->last)."\n";
		$this->last = microtime(true);
	}

	public function end() {
		echo microtime(true) - $this->start;
	}
}
?>
