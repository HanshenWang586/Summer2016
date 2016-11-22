<?php
class MapController {

/*
UPDATE `listings_data`
SET wgs84_lat= latitude + 0.003100,
wgs84_lon = longitude - 0.001399
WHERE `city_id` = 1
AND wgs84_lat = 0
AND latitude !=0
*/

	public function index() {
		global $site;
		
		$page = new Page;
		$page->setTag('scripts_lower', '<script src="/js/openlayers.js" type="text/javascript"></script>
					  <script src="/js/maps_openlayers.js" type="text/javascript"></script>
					  <script type="text/javascript">$(document).ready(function() {loadPublicMap('.$site->getSiteID().');});</script>');
		$body .= '<h1>Maps</h1>
		<div id="map" style="border:1px solid #fff;"></div>';
		$page->setTag('main', $body);
		$page->output();
	}
	
	public function markers() {
		global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT DISTINCT c.*
							FROM listings_cities c, listings_sitecitycat c2c
							WHERE live = 1
							AND site_id = ".$site->getSiteID()."
							AND c.city_id = c2c.city_id
							AND c_latitude != 0");
		
		$content .= '<?xml version="1.0"?><markers>';
		
		while ($row = $rs->getRow()) {
			$content .= '<marker>
<location_id>'.$row['city_id'].'</location_id>
<name>'.$row['city_en'].'</name>
<lat>'.$row['c_latitude'].'</lat>
<lon>'.$row['c_longitude'].'</lon>
<url>/en/listings/city/'.$row['code'].'/</url>
</marker>';
		}
		
		$content .= '</markers>';
		echo $content;
	}

	public function city() {
	
		global $site;
		$city_code = func_get_arg(0);
		
		$city = new City(City::getCityIDFromName($city_code));
		
		switch ($city_code) {
		
		case 'chengdu':
		$bbox = "11574403,3578402,11594883,3598882";
		break;
		
		case 'kunming':
		$bbox = "11421880,2871678,11442360,2892158";
		break;
		
		case 'qujing':
		$bbox = "11544888,2926553,11565368,2947033";
		break;

		case 'jinghong':
		$bbox = "11210303,2501285,11230783,2521765";
		break;
		}
		
		$map_details = "var lat = ".$city->getLatitude().";
						var lon = ".$city->getLongitude().";
						var city_id = ".$city->getCityID().";
						var city_code = '".$city->getCityCode()."';";
		
		$view = new View;
		$view->setTag('tilecache_urls', $this->getTilecacheURLs());
		$view->setTag('map_details', $map_details);
		$view->setTag('bbox', $bbox);
		$view->setTag('page_title', $city->getCityEn().' Map');
		$view->setPath($site->getTemplatePath().'/templates/full_map.html');
		echo HTTP::compress($view->getOutput());
	}
	
	private function getTilecacheURLs() {
		global $site;
		
		if ($site->getSiteID() == 1) {
			// gokunming
			if (strpos($site->getCName(), '.')) {
			return "\"http://images1.gokunming.com/images/tilecache/\",\"http://images2.gokunming.com/images/tilecache\",\"http://images3.gokunming.com/images/tilecache/\"";
			}
			else {
			return "\"http://images1.gk5/images/tilecache/\",\"http://images2.gk5/images/tilecache\",\"http://images3.gk5/images/tilecache/\"";
			}
		}
		else if ($site->getSiteID() == 2) {
			// gochengdoo
			if (strpos($site->getCName(), '.')) {
			return "\"http://images1.gochengdoo.com/images/tilecache/\",\"http://images2.gochengdoo.com/images/tilecache\",\"http://images3.gochengdoo.com/images/tilecache/\"";
			}
			else {
			return "\"http://images1.gc1/images/tilecache/\",\"http://images2.gc1/images/tilecache\",\"http://images3.gc1/images/tilecache/\"";
			}
		}
	}
}
?>