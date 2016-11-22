<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

	if (isset($admin_user)) {
		HTTP::redirect(ADMIN_ROOT_URL);
	}
?>
<html>
<head>
<link href="<?php echo ADMIN_ROOT_URL; ?>css/default.css" rel="stylesheet" type="text/css">
</head>
<body>
<table height="100%" width="100%">
<tr><td align="center" valign="middle">

<form name="loginform" method="post" action="login_proc.php">
<table border="0" cellspacing="0" cellpadding="3" align="center">
<tr><td colspan="2"><h1><?php echo ADMIN_TITLE; ?></h1></td>
</tr>
<tr><td><b>Username:</b></td><td><input type="email" name="username" size="45"></td></tr>
<tr><td><b>Password:</b></td><td><input type="password" name="password" size="45"></td></tr>
<tr><td colspan="2" align="right"><input type="submit" value="Submit"></td></tr>
</table>
</form>

</td></tr>
</table>
</body>
</html>