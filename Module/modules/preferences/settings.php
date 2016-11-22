<?php
	include_once("../../include/admin.php");
	$todo = request($_REQUEST['todo']);
	
	if ($user_details['group_id'] > 1) {
		$auth = false;
		include($ewyse_paths['include'] . 'unauth.php');
		exit();
	}

	include_once('settingsmodel.class.php');
	$settingsModel = new SettingsModel();
	
	// The message to show after changing settings.
	$message = false;
	
	if ($todo == "update" && ($settings = request($_REQUEST['settings']))) {
		$settingsModel->updateSettings($settings, $module);
		$message = "The settings have been updated succesfully";
	}

	$settings = $settingsModel->getSettings($module);
	if (!($moduleSettings = getModuleSettingsClass($module, $settings))) {
		criticalError("Error!", "No settings available for the selected module " . $module . ".");
	}
	printHTMLTopEwyse();
?>
	<title><?=ucfirst($module);?></title>
	<link href="<?=$ewyse_urls['css'];?>default.css" rel="stylesheet" type="text/css" >
	<link href="css/default.css" rel="stylesheet" type="text/css" >
	<script type="text/javascript" language="javascript">
		function onFormSubmit() {
			return confirm('Are you sure you wish to update these values?\nThe change will be permanent!');
		}
	</script>
</head>

<body>
	<div class="header">
		<div class="text">You can adjust the settings of module <span style="font-weight: bold; text-transform: uppercase;"><?=$module?></span> below.</div>
	</div>
<? 
	if ($message) {
		echo "<div class=\"infoBar\">\n";
		echo $message;
		echo "</div>\n";
	}
?>
	<div class="bodyWithHeader">
		<div style="position: static; margin: 20px;">
			<form method="post" id="form" action="<?=getenv('REQUEST_URI')?>" onSubmit="return onFormSubmit();">
				<input type="hidden" name="todo" value="update">
				<? $moduleSettings->printFormFields(); ?>
				<br>
				<input type="submit" value="submit changes">
			</form>
		</div>
	</div>
</body>
</html>
