<?
	session_start();
	if ($_SESSION['logged_in']) $public = true;
	include_once("../../../include/admin.php");
	$module = request($_REQUEST['module']);
	$preferences = request($_REQUEST['preferences']);
	$prefs = array();
	if ($preferences) {
		$preferences = explode("|", $preferences);
		for ($i = 0; $i < count($preferences); $i++) {
			$preference = explode(":", $preferences[$i]);
			if (count($preference) == 2) {
				$prefs[$preference[0]] = $preference[1];
			}
		}
	}
	if ($preferences && $module) { // If a view and a module is given, we can save these settings for next time.
		$user = request($_REQUEST['user']) == 'system' ? $_REQUEST['user'] : $_SESSION['user_details']['login'];
		$result = updateModuleSettings($prefs, $module, $user);
	}	
	Header( 'Content-Type: text/xml' ); 
	printf("<?xml version=\"1.0\"?>\n");
?>
<response><? echo ($result) ? "Saved preferences: " . request($_REQUEST['preferences']) . "; module=$module;" : "Error saving preferences"; ?></response>