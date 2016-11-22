<?php
class ListingsItem {

	/**
    * @var integer
    */
	private $status = 1;
	private $phone_code_override = 0;

	public function __construct($id = '') {
		if ($id) $this->load($id);
	}
	
	public function load($id) {
		if (ctype_digit($id)) {
			$data = $GLOBALS['model']->db()->query('listings_data', array('listing_id' => (int) $id), array(
				'join' => array('table' => 'listings_cities', 'fields' => '*', 'on' => array('city_id', 'city_id')),
				'singleResult' => true
				));
			if ($data) $this->setData($data);
		}
	}
	
	public function getFormInput() {
		global $model;
		ob_start();
?>
<script>
	function calendarSuggestLocations() {
	$('#suggested_locations').hide();
	
	var location = $('#location').val();
	
	if (location.length > 3) {
		$('#suggested_locations_loading').show();
		$('#suggested_locations').load('/en/calendar/findlocations/',
									   {location_stub: location},
									   function() {
											$('#suggested_locations_loading').hide();
											$('#suggested_locations').show();
										});
	}
}

function calendarUseSuggestedLocation(location_id) {
	$('#form_calendar_item_submit').show();
	$('#suggested_locations').empty();
	$('#selected_location').load('/en/calendar/locations/',
									{location_id: location_id});
}

function calendarLoadSuggester() {
	$('#form_calendar_item_submit').hide();
	$('#selected_location').load('/en/calendar/suggest/');
}
</script>
<?	

		$location = ob_get_clean();
		$location .= sprintf('<label id="location_label" for="location">%s</label><div class="inputWrapper" id="selected_location">', $model->lang('FORM_LOCATION_CAPTION'));

		if ($this->listing_id) {
			$location .= $this->getCalendarFormSummary();
		}
		else {
			$location .= "<input class=\"text\" id=\"location\" onkeyup=\"calendarSuggestLocations()\">
			<div id=\"suggested_locations_loading\">loading...</div>
			<div id=\"suggested_locations\"></div>";
		}

		$location .= '</div>';
		return $location;
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value)
				$this->$key = $value;
		}
	}
	
	public function getUserAddedID() {
		return ifElse($this->user_id_added, $this->public_user_id_added);
	}
	
	public function getUserAdded() {
		$userAdded = $this->getUserAddedID();
		return $userAdded ? $GLOBALS['site']->getUser($userAdded) : false;
	}
	
	public function getUserUpdated() {
		return $this->user_id_updated ? $GLOBALS['site']->getUser($this->user_id_updated) : false;
	}
	
	public function getWGS84Latitude() {
		return rtrim($this->wgs84_lat, '0');
	}
	
	public function getWGS84Longitude() {
		return rtrim($this->wgs84_lon, '0');
	}

	public function getLatitude() {
		return rtrim($this->latitude, '0');
	}

	/**
	 * @return string The longitude stored in the db longitude column
	 */
	public function getLongitude() {
		return rtrim($this->longitude, '0');
	}

	function getGoogleMapsCorrectedLongitude()
	{
	//0.001399346995
		return rtrim($this->longitude + $this->lon_correction, '0');
	}

	function getGoogleMapsCorrectedLatitude()
	{
	//0.00310459513027
		return rtrim($this->latitude + $this->lat_correction, '0');
	}

	public function getCityID() {
		return $this->city_id;
	}

	public function getCalendarFormSummary() {
		$content = "<input type=\"hidden\" name=\"listing_id\" value=\"$this->listing_id\">
		$this->name_en ($this->city_en: $this->address_en) <a href=\"javascript:void(null)\" onClick=\"calendarLoadSuggester();$('#existing_events').empty();\" class=\"highlight\">Change</a>";
		return $content;
	}

	public function getName() {
		return $this->name_en;
	}

	function setUserID($user_id) {
		$this->user_id = $user_id;
	}

	public function getURL($usePageArgs = false, $options = array()) {
		global $model;
		return $model->url(array('m' => 'listings', 'view' => 'item', 'id' => $this->listing_id, 'name' => $this->name_en), $options, $usePageArgs);
	}

	public function getLink() {
		return HTMLHelper::link($this->getURL(), $this->getPublicName());
	}
	
	function setUser($user) {
		$this->user = $user;
	}

	function setListURL($list_url) {
		$this->list_url = $list_url;
	}

	function setItemURL($item_url) {
		$this->item_url = $item_url;
	}

	function setCityID($city_id) {
		$this->city_id = $city_id;
	}

	function setTags($tags) {
		$this->tags = $tags;
	}

	function getItemURL() {
		return $this->item_url;
	}

	function setListingID($listing_id) {
		$this->listing_id = $listing_id;
	}

	function getListingID() {
		return $this->listing_id;
	}

	public function getBilingualName() {
		return trim($this->name_en.' '.$this->name_zh);
	}

	function getPageTitle() {
		return trim($this->name_en.' '.$this->name_zh);
	}

	private function getPublicPhone() {
		return $this->getPhone();
	}

	private function getPhone() {
		if (!$this->phone) return '';
		elseif (!$this->phone_code_override) return '('.$this->phone_code.') ' . $this->phone;
		else return $this->phone;
	}

	private function getPublicFax() {
		if (!$this->fax) return '';
		elseif (!$this->fax_code_override) return '('.$this->phone_code.') '.$this->fax;
		else return $this->fax;
	}

	public function getWebsite() {
		if ($this->url != '') {
			$pieces = parse_url($this->url);
			return HTMLHelper::link(addHttp($this->url), $pieces['host'] . ($pieces['path'] != '/' ? $pieces['path'] : ''), array('external' => true));
		}
		return '';
	}

	public function getCityLink($lang) {
		return $this->getCity()->getLink($lang);
	}
	
	public function getCity() {
		if (!$this->city or $this->city->getCityID() != $this->city_id) $this->city = new City($this->city_id);
		return $this->city;
	}

	public function getPublicAddress() {
		$address_en = $this->address_en != '' ? $this->address_en.', '.$this->getCityLink('en').'<br />' : $this->getCityLink('en').'<br />';
		$address_zh = $this->address_zh != '' ? $this->getCityLink('zh').$this->address_zh.'<br />' : $this->getCityLink('zh').'<br />';

		$address = $address_en != '' ? $address_en : '';
		$address .= $address_zh != '' ? $address_zh : '';

		return ContentCleaner::wrapChinese($address);
	}
	
	public function getAddress() {
		return $this->address_en;
	}
	
	public function getPublicName($del = '<br>') {
		$name = $this->name_en != '' ? $this->name_en : '';
		$name .= $this->name_zh != '' ? $del . ContentCleaner::wrapChinese($this->name_zh) : '';
		return $name;
	}
	
	public function displayPublic($for_list_use = true) {
		global $user, $model;
		
		$local = $model->db()->run_select(sprintf('
			SELECT p.province, c.city_en AS city
			FROM listings_cities c
			LEFT JOIN provinces p ON (c.province_id = p.province_id)
			WHERE c.city_id = %d
			', $this->getCityID()
		), true);
		
		$address = $this->getAddress();
		$phone = $this->getPhone();
		if (!$phone) $phone = $this->mobile;
		
		$address = $address ? sprintf('
							<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress" class="address">
								<span class="icon icon-map-pin-fill"> </span>
								<span class="street" itemprop="streetAddress">%s</span>
								<span class="locality" itemprop="addressLocality">%s</span>
								<span class="region" itemprop="addressRegion">%s</span>
							</span>',
							$address,
							$local['city'],
							$local['province']
						) : '';
		$phone = $phone ? sprintf('<span class="phone"><span class="icon icon-phone"> </span><span itemprop="telephone">%s</span></span>', $phone) : '';
		return sprintf("
				<article itemscope itemtype=\"http://schema.org/localBusiness\">
					<a itemprop=\"url\" class=\"item clearfix\" href=\"%s\">
						%s
						%s
						<h1 itemprop=\"name\">%s</h1>
						<p class=\"details\">
							%s
							%s
						</p>
					</a>
					%s
				</article>",
				$this->getURL(),
				$this->getLogo(100, 100),
				$this->getAverageStars(),
				$this->getName(),
				$address,
				$phone,
				$this->getLinkedCategoriesList()
			);
	}
	
	public function removeLogo() {
		$path = $this->getLogoPath(false ,false, true);
		if ($path) {
			$GLOBALS['model']->tool('image')->clearCache($path);
			return unlink($path);
		} else return true;
	}
	
	public function getLogoPath($width = false, $height = false, $ignoreListingImages = false) {
		global $model;
		
		if ($this->logo) $path = LISTINGS_LOGO_STORE_FILEPATH . $this->logo;
		if (!$ignoreListingImages and (!$this->logo or !file_exists($path))) {
			$row = $model->db()->query('listings_photos', array('listing_id' => $this->listing_id), array('orderBy' => 'ts', 'order' => 'DESC', 'singleResult' => true));
			if ($row) {
				$photo = new ListingsPhoto;
				$photo->setData($row);
				$path = $photo->getLargePath();
				if (!file_exists($path)) $path = $model->paths['root'] . '/assets/logo/logo-bg.png';
			} else $path = $model->paths['root'] . '/assets/logo/logo-bg.png';
		}
		
		if ($path and ($width or $height)) {
			$path = $model->tool('image')->resize($path, $width, $height, false, true);
		}
		
		return $path;
	}
	
	public function getLogoURL($width = false, $height = false, $ignoreListingImages = false) {
		global $model;
		return str_replace($model->paths['root'], '', $this->getLogoPath($width, $height, $ignoreListingImages));
	}
	
	public function getLogo($width = false, $height = false, $ignoreListingImages = false) {
		global $model;
		$path = $this->getLogoPath($width, $height, $ignoreListingImages);
		if ($path) {
			$info = getimagesize($path);
			return sprintf('<img class="logo" %s%s src="%s">', $info[3], ($width || $height) ? '' : ' itemprop="logo"', str_replace($model->paths['root'], '', $path));
		} else return '';
	}
	
	function getCategoriesList() {
		$cl = new CategoryList;
		$cl->setCategoryIDs($this->getCategoryIDs());
		return $cl->getCategoriesText();
	}

	function getCategoryIDs()
	{
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT category_id
			FROM listings_i2c
			WHERE listing_id = $this->listing_id");

		while ($row = $rs->getRow())
		{
			$category_ids[] = $row['category_id'];
		}

		return $category_ids;
	}

	public function displayPublicFull() {
		global $user, $mobile, $model, $site;
		$city = $this->getCity();

		$view = new View;
		$view->setPath('listings/full.html');
		//$user_id = ifElse($this->user_id_added, $this->public_user_id_added);
		//$view->setTag('isOwner', $user->isLoggedIn() and $user->getUserID() == $user_id);
		$view->setTag('isAdmin', $user->getPower());
		$view->setTag('live', $this->isLive());
		$view->setTag('approved', $this->approved);
		$view->setTag('listing_id', $this->listing_id);
		$view->setTag('title', $city->getName() . ' ' . $model->lang('LISTINGS'));
		
		$thumb = $this->getLogo(295, false, true);
		if ($thumb) {
			$view->setTag('logo_thumb', $thumb);
			$logoPath = $this->getLogoURL();
			$view->setTag('logo_path', $logoPath);
			$img = str_replace($model->paths['root'], $model->urls['root'], $logoPath);
			if (strpos($img, $model->urls['root']) === false) $img = $model->urls['root'] . $img;
			$site->addMeta('og:image', $img, 'property');
		}
		
		$short = '| GoKunming Listings | Restaurants, hotels, hostels, guesthouses, cafes, bars and more';
		
		$site->addMeta('og:description', $short, 'property');
		$site->addMeta('description', $short);
		$site->addMeta('og:url', $this->getURL(), 'property');
		
		$view->setTag('cityURL', $city->getURL());
		$view->setTag('name', $this->getPublicName());
		$social = new Social;
		$social_name = $this->name_en . ' – ' . $this->address_en . ', ' . $city->getName();
		$view->setTag('social', $social->getSharingList($social_name, $img, $short));
		if ($this->getLongitude() > 0) $view->setTag('map', sprintf(
			'<div class="google_map" data-zoom="16" data-longitude="%s" data-latitude="%s">
				<div class="infoHeader">
					<span class="icon icon-info"> </span>
					<span class="infoText">
						<span class="listingTitle">%s</span> • %s
					</span>
				</div>
				<div class="mapContainer"></div>
			</div>',
			$this->getLongitude(),
			$this->getLatitude(),
			$this->getName(),
			$this->address_en
		));
		$view->setTag('city_code', $city->getCityCode());
		$view->setTag('city_name', $city->getName());

		$info = array(
			'Address' => $this->getPublicAddress(),
			'Mobile' => $this->mobile,
			'Phone' => $this->getPublicPhone(),
			'Fax' => $this->getPublicFax(),
			'Hours' => str_replace(',', '<br>', $this->hours),
			'Happy Hour' => str_replace(',', '<br>', $this->happy_hour),
			'Website' => $this->getWebsite()
			);
		$content = "<ul class=\"infoList\">";
		$first = true;
		foreach($info as $label => $value) {
			if ($first) {
				$class = ' class="first"';
				$first = false;
			} else $class = '';
			if (trim($value)) $content .= sprintf("\t<li%s><span class=\"label\">%s</span><span class=\"value\">%s</span></li>\n",
								$class, $label, $value
							);
		}
		$content .= "</ul>";
		$view->setTag('info', $content);
		$view->setTag('url', $this->getURL());
		$view->setTag('categories', $this->getLinkedCategoriesList());
		$view->setTag('events', $this->getEvents());
		$view->setTag('events_count', $this->eventsCount);
		$view->setTag('reviews_count', $this->getNumReviews());
		$view->setTag('userAdded', $this->getUserAdded());
		
		$view->setTag('timeAdded', $model->tool('Datetime')->getDateTag($this->ts_added));
		$view->setTag('userUpdated', $this->getUserUpdated());
		$view->setTag('timeUpdated', $model->tool('Datetime')->getDateTag($this->ts_updated));
		$view->setTag('reviews', $this->getReviews());
		if ($this->description) $view->setTag('description', ContentCleaner::PWrap(ContentCleaner::linkHashURLs($this->description)));
		$view->setTag('images', $this->getPhotos());
		$view->setTag('stars', $this->getAverageStars());
		
		if ($user->isLoggedIn()) {
			$form = new View;
			$form->setPath('listings/form_review.html');
			$form->setTag('listing_id', $this->listing_id);
			$form->setTag('nickname', $user->getNickname());
			$view->setTag('form_review', $form->getOutput());
		} else $view->setTag('form_review', sprintf(
						"<div class=\"buttons\">
							<a class=\"icon-link\" href=\"/en/users/login/\"><span class=\"icon icon-login\"> </span>%s</a>
							<a class=\"icon-link\" href=\"/en/users/register/\"><span class=\"icon icon-user-add\"> </span>%s</a>
						</div>",
						$view->lang('LOGIN_TO_REVIEW', 'ListingsModel'),
						$view->lang('REGISTER_TO_REVIEW', 'ListingsModel')
					));
	
		return $view->getOutput();
	}

	public function getLinkedName($metadata = false) {
		$name = $this->name_en;
		$meta = '';
		if ($metadata) {
			$name = sprintf('<span itemprop="name">%s</span>', $name);
			$meta = ' itemprop="url"';
		}
		return sprintf('<a%s href="%s">%s</a>', $meta, $this->getURL(), $name);
	}

public function getListItem() {
	return '<a href="'.$this->getURL().'">'.$this->getName().'</a><br />'.$this->getAddress();
}

function displayAdminRow()
{
	if ($this->getLatitude() != 0)
	{
		$map_text = '<b>Map</b>';
	}
	else
	{
		$map_text = 'Map';
	}

	$content = "<tr valign=\"top\">
	<td><a href=\"".$this->getURL()."\" target=\"_blank\">".$this->getName()."</a><br />
	<span class=\"chinese\">$this->name_zh</span><br />
	<br />
	$this->address_en, $this->city_en<br />
	<span class=\"chinese\">$this->city_zh市$this->address_zh</span><br />
	<br />
	Tel: ".$this->getPhone()."<br />
	".$this->getWebsite()."</td>
	<td>".$this->getCategoriesList()."</td>
	<td>$this->status";

	if ($this->status==4)
	{
		$content .= '<br />'.$this->nickname.'<br />'.$this->site_name;
	}

	if ($this->ts_squashed != '0000-00-00 00:00:00')
	{
		$content .= '<br />'.$this->ts_squashed;
	}

	$content .= "</td></tr>";

	return $content;
}

public function displayPublicForm($mobile = false) {
	global $user, $site;

	if ($this->listing_id)
		$content .= "<a href=\"".$this->getURL()."\">Go back to item, without saving</a><br /><br />";

	$content .= FormHelper::open('/en/listings/form_proc/');
	if ($this->listing_id) $content .= FormHelper::hidden('listing_id', $this->listing_id);

	$f[] = FormHelper::input('English Name', 'name_en', $this->name_en);
	$f[] = FormHelper::input('Chinese Name', 'name_zh', $this->name_zh, array('class' => 'chinese'));

	$content .= FormHelper::fieldset('Name', $f);

	$f[] = FormHelper::input('English Address', 'address_en', $this->address_en);
	$f[] = FormHelper::input('Chinese Address', 'address_zh', $this->address_zh, array('class' => 'chinese'));
	$f[] = FormHelper::select('City', 'city_id', CityList::getArray(), $this->city_id ? $this->city_id : $site->getHomeCity()->getCityID());
	$f[] = FormHelper::input('Opening hours', 'hours', $this->hours);
	$f[] = FormHelper::input('Happy hour', 'happy_hour', $this->happy_hour);
	$f[] = FormHelper::input('Mobile Phone', 'mobile', $this->mobile);
	$f[] = FormHelper::input('Phone', 'phone', $this->phone);
	$f[] = FormHelper::checkbox('No area code', 'phone_code_override', $this->phone_code_override);
	$f[] = FormHelper::input('Fax', 'fax', $this->fax);
	$f[] = FormHelper::checkbox('No area code', 'fax_code_override', $this->phone_code_override);
	$f[] = FormHelper::input('Website', 'url', $this->url);
	$f[] = FormHelper::textarea('Description', 'description', $this->description);
	$f[] = FormHelper::select('Status', 'status', $this->getStatuses(), $this->status);
	$f[] = FormHelper::submit();
	
	$content .= FormHelper::fieldset('Location & Contact', $f);

	$content .= FormHelper::close();
	return $content;
}

public function displayCategoriesForm() {
	global $user;

	$category_list = new CategoryList;

	$content .= "<div id=\"listings_chosen_categories\">".$this->getChosenCategories()."</div>
	<br />".$category_list->getPublicSelect($this->listing_id)."<br />
	<input type=\"button\" value=\"Done\" onclick=\"location.href='/en/listings/edit_map/".$this->getListingID()."/'\">";

	return $content;
}

public function savePosition($lat, $lon) {
	global $user;
	$power = $user->getPower();
	
	$lat = ($lat == 0) ? 'NULL' : $lat;
	$lon = ($lon == 0) ? 'NULL' : $lon;

	if ($power) {
		$db = new DatabaseQuery;
		$db->execute("	UPDATE listings_data
			SET latitude = $lat,
			longitude = $lon
			WHERE listing_id = $this->listing_id");
	}
}

public function getLargeMapPickerHTML() {
	$content .= "<div id=\"google_maps_item_large\" data-listing-id=\"$this->listing_id\"></div>
	<input class=\"button\" type=\"button\" onclick=\"location.href='/en/listings/map_suggest/$this->listing_id/0/0/'\" value=\"Remove\"> <input class=\"button\" type=\"button\" value=\"Done\" onclick=\"location.href='".$this->getURL()."/'\">";
	return $content;
}

function getLatLonManual()
{
	return $this->latlon_manual;
}

function saveLatLon($is_manual = true) {
	$manual = $is_manual ? 1 : 0;

	if ($this->listing_id && isset($this->latitude) && isset($this->longitude)) {
		execute("	UPDATE listings_data
			SET latitude=$this->latitude,
			longitude=$this->longitude,
			latlon_manual=$manual
			WHERE listing_id=$this->listing_id");
	}

	$this->saveWGS84LatLon();
}

function saveWGS84LatLon()
{
	if ($this->listing_id && isset($this->wgs84_lat) && isset($this->wgs84_lon))
	{
		execute("	UPDATE listings_data
			SET wgs84_lat=$this->wgs84_lat,
			wgs84_lon=$this->wgs84_lon,
			latitude=0,
			longitude=0
			WHERE listing_id=$this->listing_id");
	}
}

function saveMap()
{
	if ($this->listing_id != '')
	{
		execute("	UPDATE listings_data
			SET latitude=$this->latitude,
			longitude=$this->longitude
			WHERE listing_id=$this->listing_id");

		if (mysql_affected_rows() > 0)
		{
			execute("	UPDATE listings_data
				SET ts_updated=NOW(),
				user_id_updated=$this->user_id
				WHERE listing_id=$this->listing_id");
		}
	}
}

public function isLive() {
	return $this->status;
}

public function delete() {
	$db = new DatabaseQuery;
	$db->execute('	UPDATE listings_data
		SET status = 0
		WHERE listing_id = '.$this->listing_id);
	$db->execute('	DELETE FROM listings_i2c
		WHERE listing_id = '.$this->listing_id);
	$db->execute('	UPDATE listings_tags
		SET live = 0
		WHERE listing_id = '.$this->listing_id);
	$db->execute('	UPDATE listings_reviews
		SET live = 0
		WHERE listing_id = '.$this->listing_id);
}

	/**
	 * Updates the database record's squash time to now
	 */
	public function squash() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE listings_data
			SET ts_squashed = NOW()
			WHERE listing_id = '.$this->listing_id);
	}

	function superDelete() {
		$db = new DatabaseQuery;
		$db->execute('	DELETE FROM listings_data
			WHERE listing_id = '.$this->listing_id);
		$db->execute('	DELETE FROM listings_i2cvotes
			WHERE listing_id = '.$this->listing_id);
		$db->execute('	DELETE FROM listings_tags
			WHERE listing_id = '.$this->listing_id);
		$db->execute('	DELETE FROM listings_reviews
			WHERE listing_id = '.$this->listing_id);
	}

	function getReviewsLink() {
		$text = 'Reviews';
		$num_reviews = $this->getNumReviews();
		$text .= $num_reviews==0 ? '' : "&nbsp;($num_reviews)";
		$content = "<a href=\"".$this->getURL()."\" class=\"ccl_link\">$text</a>";
		return $content;
	}

	private function getMoreLink() {
		return "<div style=\"float: right;width:100%; text-align:right;\"><a href=\"".$this->getURL()."\" class=\"arrow_right\">More</a></div>";
	}

	function getNumReviews() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT COUNT(*) AS num_reviews
			FROM listings_reviews
			WHERE live = 1
			AND listing_id = $this->listing_id");
		$row = $rs->getRow();
		return $row['num_reviews'];
	}

	private function getPhotos() {
		global $model, $user;
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT photo_id
			FROM listings_photos
			WHERE listing_id = '.$this->listing_id.'
			ORDER BY ts DESC');
		
		$photo = new ListingsPhoto;
		$photos = '';
		while ($row = $rs->getRow()) {
			$photo->setData($row);
			$path = $photo->getLargePath();
			$thumb = str_replace($model->paths['root'], '', $model->tool('image')->resize($path, 250, 250, false, true));
			$full = str_replace($model->paths['root'], '', $path);
			if ($user->getPower()) $delete = '<br><a href="/en/listings/photo_delete/'.$row['photo_id'].'/">Delete</a>';
			$photos .= sprintf('
				<span class="thumbnail" itemprop="photo" itemscope itemtype="http://schema.org/ImageObject">	
					<a class="lightbox" itemprop="contentURL" href="%s"><img itemprop="thumbnailUrl" width="250" height="250" src="%s"></a>%s
				</span>',
				$full,
				$thumb,
				$delete
			);
		}
		return $photos;
}

public function getAdminPhotos() {
	global $model;
	$db = new DatabaseQuery;
	$rs = $db->execute('SELECT photo_id, ts
		FROM listings_photos
		WHERE listing_id = '.$this->listing_id.'
		ORDER BY ts DESC');
	if ($rs->getNum()) {
		$photo = new ListingsPhoto;
		while ($row = $rs->getRow()) {
			$photo->setData($row);
			$path = $photo->getLargePath();
			$thumb = str_replace($model->paths['root'], '', $model->tool('image')->resize($path, 250, 250, false, true));
			$content .= '
			<span class="thumbnail">
				<img width="250" height="250" src="' . $thumb . '"><br>
				<a href="/en/listings/photo_delete/'.$row['photo_id'].'/">Delete</a>
			</span>';
		}
	}

	return $content;
}

private function getEvents() {
	$cal = new Calendar;
	$events = $cal->getListingEvents($this->listing_id);
	$this->eventsCount = count($events);
	return $cal->sprintEvents($events, 'list');
}

public function getReviews() {
	global $model, $site;
	if ($this->allow_reviews) {
		if ($this->getNumReviews() == 0)
			return "<p class=\"infoMessage\">There are currently no reviews for this item. Log in and be the first to review!</p>";
		else {
			$db = new DatabaseQuery;
			$pager = new Pager;
			$pager->setLimit(5);
			$rs = $pager->setSQL('
					SELECT lr.*, u.nickname
					FROM listings_reviews lr
					LEFT JOIN public_users u ON u.user_id = lr.user_id 
					WHERE lr.listing_id = '.$this->listing_id.'
					AND lr.live = 1
					ORDER BY ts DESC
				');
			$reviews = '';
			$review = new Review;
			while ($row = $rs->getRow()) {
				$review->setData($row);
				$reviews .= $review->display($this);
			}
			$reviews .= $pager->getNav();
			return $reviews;
		}
	}
}

private function getStarsCode($rating) {
	$stars = '';
	for ($i = 1; $i < 6; $i++) $stars .= $i <= $rating ? '&#xe00f;' : '&#xe011;';
	return sprintf('<span class="icon">%s</span>', $stars);
}

private function getAverageStars() {
	global $model;
	$rs = $model->db()->run_select("SELECT ROUND(AVG(stars)) AS average, COUNT(stars) AS total
		FROM listings_reviews
		WHERE listing_id = $this->listing_id
		AND stars != -1
		AND ts > DATE_SUB(NOW(), INTERVAL 9 MONTH)
		AND live = 1", true);
	if ($rs['average'] > 0) {
		return sprintf('
			<div itemprop="aggregateRating" class="rating listings_stars" itemscope itemtype="http://schema.org/AggregateRating">
    			<meta itemprop="ratingValue" content="%d">
				<meta itemprop="ratingCount" content="%d">
				%s
  			</div>',
			$rs['average'],
			$rs['total'],
			$this->getStarsCode($rs['average'])
		);
	}

	return '';
}

private function getStatuses() {
	$statuses[0] = 'Unlive';
	$statuses[1] = 'Live';

	return $statuses;
}

function displayAdminCategories() {
	$content = "<table cellspacing=\"1\" class=\"gen_table\">
	<tr>
	<td><b>Category</b></td>
	<td><b>User</b></td>
	<td><b>Time</b></td>
	<td></td>
	</tr>";
	$rs = execute("	SELECT *
		FROM listings_i2cvotes i2c, public_users u
		WHERE listing_id = $this->listing_id
		AND u.user_id = i2c.user_ida
		ORDER BY ts DESC");

	while ($row = get_row($rs)) {
		$lc = new Category($row['category_id']);
		$nickname = str_replace('Guest', 'Admin', $row['nickname']);
		
		$content .= "<tr>
		<td>".$lc->displayUnlinkedBreadcrumb()."</td>
		<td>$nickname</td>
		<td>{$row['ts']}</td>
		<td><a href=\"form_remove_category_proc.php?listing_id=$this->listing_id&category_id={$row['category_id']}\">Remove</a></td>
		</tr>";
	}
	$content .= "</table><br />";
	return $content;
}

public function addCategory($category_id) {
	global $user;

	if ($user->getPower()) {
		$this->removeCategory($category_id);
		$db = new DatabaseQuery;
		$db->execute("	INSERT INTO listings_i2c (	listing_id,
			category_id)
		VALUES (	$this->listing_id,
			$category_id)");
		$this->squash();
	}
}

public function removeCategory($category_id) {
	global $user;

	if ($user->getPower()) {
		$db = new DatabaseQuery;
		$db->execute("	DELETE FROM listings_i2c
			WHERE listing_id = $this->listing_id
			AND category_id = $category_id");
		$this->squash();
	}
}

private function getLinkedCategoriesList() {
	global $user;
	$user->setViewingCityID($this->city_id);

	$links = array();
	$db = new DatabaseQuery;
	$rs = $db->execute('SELECT c.*
		FROM listings_categories c
		LEFT JOIN listings_i2c i2c ON (c.category_id = i2c.category_id)
		WHERE listing_id = '.$this->listing_id.'
		ORDER BY category_en');

	while ($row = $rs->getRow()) {
		$category = new Category;
		$category->setListURL($this->list_url);
		$category->setData($row);
		$category->setCityID($this->city_id);
		$links[] = $category->getLink();
	}

	return HTMLHelper::wrapArrayInUl($links, false, 'tags');
}

public function getAdminCategories() {
	global $user;
	$user->setCityID($this->city_id);

	$links = array();
	$db = new DatabaseQuery;
	$rs = $db->execute('SELECT c.*
		FROM listings_categories c
		LEFT JOIN listings_i2c i2c ON (c.category_id = i2c.category_id)
		WHERE listing_id = '.$this->listing_id.'
		ORDER BY category_en');

	if ($rs->getNum() == 0)
		return 'No categories - please select below:';

	while ($row = $rs->getRow()) {
		$category = new Category($row['category_id']);
		$links[] = $category->displayUnlinkedBreadcrumb();
	}

	return HTMLHelper::wrapArrayInUl($links);
}

function addError($error) {
	$this->errors[] = $error;
}

function getErrorCount() {
	return count($this->errors);
}

function getDatum($data_tag) {
	return $this->$data_tag;
}

function displayErrors()
{
	if ($this->getErrorCount())
	{
		$content = "<fieldset><legend><span class=\"form_highlight\">Please check</span></legend>".
		nl2br(implode("\n", $this->errors))."
		</fieldset><br />";
	}
	return $content;
}

function displayPhotoForm() {
	global $user;

	$photo = new ListingsPhoto;
	$photo->setListingID($this->listing_id);
	$content = $photo->displayForm();

	return $content;
}

function displayCategoriesCheckboxes() {
	$lcl = new CategoryList;
	$content .= $lcl->getPublicCheckboxes($this->listing_id);
	return $content;
}

public function getChosenCategories() {
	global $user;

	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
		FROM listings_categories c
		LEFT JOIN listings_i2c i2c ON (c.category_id = i2c.category_id)
		WHERE i2c.listing_id = $this->listing_id");

	if ($rs->getNum() > 0) {
		while ($row = $rs->getRow()) {
			$category = new Category;
			$category->setData($row);
			$links[] = $category->displayUnlinkedBreadcrumb()." <a href=\"javascript:void(null);\" onClick=\"ajax_action_categorize_remove($this->listing_id, {$row['category_id']})\">Remove</a>";
		}
	}

	return HTMLHelper::wrapArrayInUl($links);
}
}
?>
