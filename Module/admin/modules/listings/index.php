<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings');

$ss = stripslashes($_GET['ss']);
$city_id = $_GET['city_id'];
$search_array = array(	'city_id' => $city_id,
						'ss' =>$ss);

// $citylist = new CityList;

// TODO get this onto FormHelper
/*$body .= "<form action=\"index.php\" method=\"get\">
<b>City</b> ".$citylist->displayAdminSelect($city_id)."<br />
<br />
<b>Search</b> <input name=\"ss\" value=\"$ss\" class=\"chinese\">
<input type=\"submit\" value=\"Search\">
</form>


<br />";*/

$body .= FormHelper::open('index.php');
$body .= FormHelper::submit('Search');
$f[] = FormHelper::select('City', 'city_id', array('' => 'All') + CityList::getArray(), $city_id);
$f[] = FormHelper::input('Search', 'ss', '', array('class' => 'chinese'));
$body .= FormHelper::fieldset('Search', $f);
$body .= FormHelper::submit('Search');
$body .= FormHelper::close();


	if ($ss != '' || $city_id != '') {
		// set up pagination
		$pager = new AdminPager;
		$pager->setLimit(20);
		$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);
	
		$ll = new ListingsList;
		$data = $ll->displayAdmin($search_array, $pager);
	
		$body .= $pager->getNav();
		$body .= $data;
		$body .= $pager->getNav();
	}

$pap->setTag('main', $body);
$pap->output();
?>