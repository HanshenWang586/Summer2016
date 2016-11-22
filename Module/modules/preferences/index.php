<?
	include_once("../../include/admin.php");
	if(!$module = request($_REQUEST['module'])){
		$module = 'site';
	}
	if($module == 'preferences'){
		$module = 'site';
	}

	$todo = request($_REQUEST['todo']);

	include_once('settingsmodel.class.php');
	$settingsModel = new SettingsModel();
	if ($todo == "update" && ($settings = request($_REQUEST['settings']))) {
		$settingsModel->updateSettings($settings, $module);
		$message = "The settings have been updated succesfully";
						
		$settings = $settingsModel->getSettings($module);
		$moduleSettings = getModuleSettingsClass($module, $settings);
		if($moduleSettings && method_exists($moduleSettings, 'afterUpdateSettings')){
			$moduleSettings->afterUpdateSettings();
		}
	}

	function addButtons(){
		?>
			<tfoot>
				<tr>
					<td colspan="3">
						<input type="submit" value="apply">
						<input type="button" value="reset" onClick="document.location.reload();">
					</td>
				</tr>
			</tfoot>
		<?					
	}
	printHTMLTopEwyse("en", false, false, 'strict', true);
?>
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
	</div>
	<div class="bodyWithHeader">	
		<div style="overflow: auto; height:100%; width:100%; zoom: 1;">
			<div id="headerLayer">
				<span>Preferences ewyse cms for <?=$_SESSION['managingSite']?></span>
				<div id="moduleTabs">
					
				<?
					if($module == 'site'){
						$class=" class=\"activeItem\"";
					} else {
						$class = '';
					}
					printf("\t\t\t<a%s href=\"?module=%s\">%s</a>\n", $class, 'site', 'site');
					$modulesTools = getToolsClass('modules');
					$modules = $modulesTools->getEnabledModules();
			
					foreach($modules as $row) {
						if (file_exists($modulePath . $row['name'] . "/settings/class.settings.php")) {
							$url = sprintf("settings.php?module=%s", $row['name']);
							$icon = $modulePath . $row['name'] . '/img/system/module_icon_small.gif';
							$icon = !file_exists($icon) ? false : $ewyse_urls['module'] . $row['name'] . '/img/system/module_icon_small.gif';
							if($module == $row['name']){
								$class=" class=\"activeItem\"";
							} else {
								$class = '';
							}
							printf("\t\t\t<a%s href=\"?module=%s\">%s</a>\n", $class, $row['name'], $row['name']);
						}
					}
				?>
				</div>
			</div>
			
			<div>
				<form method="post" id="form" action="<?=getenv('REQUEST_URI')?>" onSubmit="return onFormSubmit();">
					<input type="hidden" name="todo" value="update">
					<?
						$settings = $settingsModel->getSettings($module);
						if ($moduleSettings = getModuleSettingsClass($module, $settings)) {
							$moduleSettings->printFormFields();
						}
					?>
				</form>
			</div>
		</div>
	</div>	
</body>
</html>
