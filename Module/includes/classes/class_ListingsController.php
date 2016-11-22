<?php
class ListingsController {
	
	/**
	 * Redirects based on home city of the site being viewed
	 */
	public function index() {
		global $site;
		$city = $site->getHomeCity();
		HTTP::redirect('/en/listings/city/'.$city->getCityCode().'/', 301);
	}

	public function search() {
		global $user;
		$ls = new ListingsSearch;
		$ls->setCityID($_GET['city_id']);
		$ls->setSearchString($_GET['ss']);
		echo HTTP::compress($ls->getResults());
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
	
	public function logo($id = false) {
		global $user, $model;
		
		if (!$id or !is_numeric($id)) HTTP::Throw404();
		$li = new ListingsItem($id);
		if (!$li->getListingID()) HTTP::Throw404();
		
		// If the user is not authorized, get out of here
		if (!$user->isLoggedIn() or (
			!$user->getPower() and
			($id and $li->getUserAddedID() != $user->getUserID())
		)) HTTP::disallowed();
		
		$uploader = $model->tool('uploader');
		
		$logo = $li->getLogo(295, false, true);
		if ($uploader->exists('file')) {
			$uploader->setUploadFolder(LISTINGS_LOGO_STORE_FILEPATH);
			if ($uploader->captureUpload('file')) {
				$file = $uploader->successful[0]['target'];
				if ($model->tool('image')->resize($file, 800, 800, true) and $model->db()->update('listings_data', array('listing_id' => $li->getListingID()), array('logo' => $uploader->successful[0]['name']))) {
					if ($logo) $li->removeLogo();
					HTTP::redirect($li->getURL());
				}
			}
		}
		
		$p = new Page();
		
		$title = $model->lang('EDIT_LOGO', 'ListingsModel', false, true);
		$content = sprintf("<h1 class=\"dark\">%s</h1><section id=\"event\" class=\"row\"><h1><a href=\"%s\">%s</a></h1>", $title, $li->getURL(), $li->getPublicName());

		if ($logo) {
			$info = getimagesize($li->getLogoPath());
			$content .= sprintf('
				<div class="span3 pull-right">
					%s
					<div class="whiteBox pull-right">
						<small>%s</small>
						<p>%s: %dx%dpx</p>
					</div>
				</div>
			',
				$logo,
				$model->lang('IMAGE_INFO', 'ListingsModel'),
				$model->lang('DIMENSIONS', 'ListingsModel'),
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
			$model->lang('UPLOAD_LOGO_FORM', 'ListingsModel'),
			$model->lang('MAX_UPLOAD_SIZE', 'ListingsModel'),
			formatSize($uploader->getMaxUploadSize()),
			$model->lang('FORM_LOGO_UPLOAD_CAPTION', 'ListingsModel'),
			$model->lang('FORM_SAVE_LOGO', 'ListingsModel', false, true)
		);
		
		$content .= '</section>';
		$p->setTag('page_title', $title);
		$p->setTag('main', $content);
		$p->output();
	}
	
	public function review_proc() {
		$review = new Review;
		$review->setData($_POST);
		$review->savePublic();
		$li = new ListingsItem($_POST['listing_id']);
		HTTP::redirect($li->getURL());
	}

	public function city($cityName = false, $catName = false) {
		global $user, $model, $site;
		
		if (!$cityName) $this->index();
		$city = new City(City::getCityIDFromName($cityName));
		if (!$cityID = $city->getCityID()) HTTP::throw404();
		
		$catID = false;
		if ($catName) {
			if (!$cat = $model->db()->query('listings_categories', array('category_code' => $catName), array('singleResult' => true))) HTTP::throw404();
			$catID = $cat['category_id'];
		}
		
		$p = new Page();
			
		$view = new View;
		$view->setPath('listings/city.html');
		$view->setTag('city', $city->getName());
		$title = $city->getName() . ' ' . $model->lang('LISTINGS');
		
		// categories
		$listings = new Listings;
		$view->setTag('categories', $listings->getCategories($cityID));
		$pager = new Pager;
		
		$base = '/' . $model->lang . '/listings/itemlist/'.$cityName.'/';
		if ($cat) {
			$base .= $catName . '/';
			$title .= ': ' . $cat['category_' . $model->lang];
		}
		
		$view->setTag('map', $city->getMapHTML($catID));
		
		$view->setTag('title', $title);
		
		// change city
		$city_list = new CityList;
		$view->setTag('cities', $city_list->getPickList());
		
		$listings_list = new ListingsList;
		$items = $listings_list->getListings($pager, $cityID, request($cat['category_id']), request($model->args['search']));
		
		$view->setTag('searchInfo', $pager->getText());
		$view->setTag('items', $items);
		$view->setTag('pagination', $pager->getNav());
		$p->setTag('page_title', $title);
		$p->setTag('main', $view->getOutput());
		$p->output();
	}

	public function itemlist($cityName = false, $catName = false) {
		if (!$catName) HTTP::redirect('/en/listings/city/'.$cityName.'/');
		$this->city($cityName, $catName);
		return;
	}

	/**
	 * Shows an individual listings item
	 */
	public function item($listing_code = false) {
		global $model, $site;
		// No listing selected? Bye bye
		if (!$listing_code) HTTP::throw404();
		
		// Get the id in the old and the new way (old = aaa_12345, new = 12345)
		$id = strpos($listing_code, '_') > 0 ? array_get(explode('_', $listing_code), 1) : $listing_code;
		if (!ctype_digit($id) or !$id > 0) HTTP::throw404();
		
		// Do we have an active listing?
		$li = new ListingsItem($id);
		
		if (!$li->isLive()) HTTP::throw410();
		elseif (!$li->listing_id) HTTP::throw404();
		$canonical = str_replace('&amp;', '&', $li->getURL(true, array('amp' => '&')));
		
		// Redirect to the proper address if necessary
		if (request($_SERVER['REQUEST_URI']) and $canonical != $model->urls['root'] . $_SERVER['REQUEST_URI']) HTTP::redirect($canonical);
		
		// Create page
		$p = new Page();
		
		$p->setTag('page_title', $li->getBilingualName());
		$p->setTag('main', $li->displayPublicFull());
		
		$p->output();
	}

	public function reviews() {
		global $user;

		$p = new Page();
		$pager = new Pager;
		
		$rl = new ReviewList;
		
		$view = new View;
		$view->setPath('listings/all_reviews.html');
		$view->setTag('content', $rl->getReviews($pager, false, true));
		$view->setTag('pagination', $pager->getNav());

		$p->setTag('page_title', 'All Reviews');
		$p->setTag('main', $view->getOutput());
		$p->output();
	}

	public function edit($listing_code = false) {
		if ($listing_code) {
			// Get the id in the old and the new way (old = aaa_12345, new = 12345)
			$id = strpos($listing_code, '_') > 0 ? array_get(explode('_', $listing_code), 1) : $listing_code;
			if (!ctype_digit($id) or !$id > 0) HTTP::throw404();
		} else HTTP::throw404();
		$this->editListing($id);
	}

	public function add() {
		$this->editListing();
	}
	
	private function editListing($listing_id = false) {
		global $user, $model;
		// If the user is not authorized, get out of here
		if (!$user->getPower()) HTTP::disallowed();
		
		if (!empty($_POST)) {
			$fields = array(
				'name_en',
				'name_zh',
				'address_en',
				'address_zh',
				'hours',
				'happy_hour',
				'phone',
				'mobile',
				'fax',
				'email',
				'url',
				'description',
				'city_id',
				'phone_code_override',
				'phone_code_override'
			);
			
			$data = array_select_keys($fields, $_POST);
			
			$data['url'] = preg_replace('#^http://#', '', $data['url']);
			
			$data['ts_updated'] = $data['ts_squashed'] = unixToDatetime();
			$data['user_id_updated'] = $user->getUserID();
			
			$db = $GLOBALS['model']->db();
			if ($data['name_en']) {
				if (ctype_digit($listing_id) and $listing_id > 0) {
					if ($db->update('listings_data', array('listing_id' => $listing_id), $data)) $id = $listing_id;
				} else {
					$data['user_id_added'] = $data['user_id_updated'];
					$data['ts_added'] = $data['ts_updated'];
					$id = $db->insert('listings_data', $data);
				}
			}
			if ($id)
				HTTP::redirect('/en/listings/edit_categories/' . $id);
			else
				HTTP::redirect('/en/listings/edit/' . $id);
		}
		
		$p = new Page;
		$title = $model->lang($listing_id ? 'EDIT_LISTING_TITLE' : 'ADD_LISTING_TITLE', 'ListingsModel', false, true);
		
		$body .= sprintf('<h1 class="dark">%s</h1>', $title);
		if ($listing_id) {
			$listing = new ListingsItem($listing_id);
			$url = $listing->getURL();
			$body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $url, $model->lang('BACK_TO_LISTING', 'ListingsModel'));
		}
				
		$form = isset($_SESSION['add_listing_form']) ? $_SESSION['add_listing_form'] : new AddListingForm;
		
		if ($listing_id) {
			$data = $model->db()->query('listings_data', array('listing_id' => $listing_id), array('singleResult' => true));
			if ($data) {
				$form->listing_id = $listing_id;
				$form->setData($data);
			} else {
				HTTP::throw404();
			}
		}
		
		$body .= $form->display();
	
		unset($_SESSION['add_listing_form']);
		
		$p->setTag('page_title', $title);
		$p->setTag('main', $body);
		$p->output();
	}
	
	public function edit_categories($listing_code = false) {
		global $user;
		
		// If the user is not authorized, get out of here
		if (!$user->getPower()) HTTP::disallowed();
		
		if (!$listing_code) HTTP::throw404();
		
		// Get the id in the old and the new way (old = aaa_12345, new = 12345)
		$id = strpos($listing_code, '_') > 0 ? array_get(explode('_', $listing_code), 1) : $listing_code;
		if (!ctype_digit($id) or !$id > 0) HTTP::throw404();
		
		$item = new ListingsItem($id);
		if (!$item->listing_id) HTTP::throw404();
		
		$p = new Page;
		$body .= '<h1 class="dark">'.$item->getName().'</h1>';
		$body .= $item->displayCategoriesForm();

		$p->setTag('page_title', 'Item Categories');
		$p->setTag('main', $body);
		$p->output();
	}

	public function edit_map($listing_code = false) {
		global $user;
		
		if (!$listing_code) HTTP::throw404();
		
		// Get the id in the old and the new way (old = aaa_12345, new = 12345)
		$id = strpos($listing_code, '_') > 0 ? array_get(explode('_', $listing_code), 1) : $listing_code;
		if (!ctype_digit($id) or !$id > 0) HTTP::throw404();
		
		// If the user is not authorized, get out of here
		if (!$user->getPower()) HTTP::disallowed();
		
		$item = new ListingsItem($id);
		if (!$item->listing_id) HTTP::throw404();

		$p = new Page;
		$body = '<h1 class="dark">Listings: Edit Map</h1><h2>'.$item->getLink().'</h2>';
		$body .= $item->getPublicAddress().'<br />';
		$body .= $item->getLargeMapPickerHTML();

		$p->setTag('page_title', 'Map Item');
		$p->setTag('main', $body);
		$p->output();
	}

	public function map_suggest($listing_id, $lat = 0, $lon = 0) {
		global $user;
		if ($user->getPower()) {
			$item = new ListingsItem($listing_id);
			$item->savePosition($lat, $lon);

			if ($lat == 0 && $lon == 0)
				HTTP::redirect($item->getURL());
		}
	}

	public function city_json($city_id = false, $cat_id = false) {
		global $model;
		$ll = new ListingsList;
		$sql = $ll->getListingsSQL($city_id, $cat_id, request($model->args['search']));
		
		$db = new DatabaseQuery;
		$rs = $db->execute($sql);
		
		$item = new ListingsItem;
		while ($row = $rs->getRow()) {
			if ($row['latitude'] > 0) {
				$item->setData($row);
				$items[] = array(
					'name' => $item->getName(),
					'address' => $item->getAddress(),
					'lat' => $item->getLatitude(),
					'lon' => $item->getLongitude(),
					'listing_id' => $item->getListingID(),
					'url' => $item->getURL()
				);
			}
		}
		
		header('Pragma: public');
		header('Cache-Control: max-age='.(12*60*60));
		header('Expires: '.date('r', time()+12*60*60));
		header('Content-type: application/json; charset=utf-8');

		echo HTTP::compress(json_encode($items));
	}

	public function item_json() {
		header('Content-type:application/json;charset=utf-8');
		$listing_id = func_get_arg(0);
		$i = new ListingsItem($listing_id);
		if ($i->getLatitude() == 0) {
			$city = new City($i->getCityID());
			$item['lat']		= $city->getLatitude();
			$item['lon']		= $city->getLongitude();
		}
		else {
			$item['lat']		= $i->getLatitude();
			$item['lon']		= $i->getLongitude();
		}
		echo json_encode($item);
	}

	public function add_category() {
		global $user;
		if ($user->getPower()) {
			$li = new ListingsItem($_GET['listing_id']);
			$li->addCategory($_GET['category_id']);
			echo $li->getChosenCategories();
		}
	}

	public function remove_category() {
		global $user;
		if ($user->getPower()) {
			$li = new ListingsItem($_GET['listing_id']);
			$li->removeCategory($_GET['category_id']);
			echo $li->getChosenCategories();
		}
	}

	public function form_photo($listing_code = false) {
		global $user;
		if (!$listing_code) HTTP::throw404();
		
		// Get the id in the old and the new way (old = aaa_12345, new = 12345)
		$id = strpos($listing_code, '_') > 0 ? array_get(explode('_', $listing_code), 1) : $listing_code;
		if (!ctype_digit($id) or !$id > 0) HTTP::throw404();
		
		// If the user is not authorized, get out of here
		if (!$user->getPower()) HTTP::disallowed();
		
		$item = new ListingsItem($id);
		if (!$item->listing_id) HTTP::throw404();

		$p = new Page;
		$body .= '<section id="listing"><h1 class="dark">Edit photos</h1><h2>'.$item->getLink().'</h2>';
		$body .= $item->displayPhotoForm();
		$body .= $item->getAdminPhotos();
		$body .= '</section>';
		
		$p->setTag('page_title', 'Item Photos');
		$p->setTag('main', $body);
		$p->output();
	}

	public function form_photo_proc() {
		global $user;
		if ($user->getPower()) {
			$photo = new ListingsPhoto;
			$photo->setListingID($_POST['listing_id']);
			$photo->save($_FILES['file']);
			$item = new ListingsItem($_POST['listing_id']);
			$item->squash();
			HTTP::redirect('/en/listings/form_photo/'.$item->getListingID().'/');
		}
	}

	public function photo_delete() {
		global $user;
		if ($user->getPower()) {
			$photo = new ListingsPhoto(func_get_arg(0));
			$item = new ListingsItem($photo->getListingID());
			$photo->delete();
			HTTP::redirect('/en/listings/form_photo/'.$item->getListingID().'/');
		}
	}
}
?>
