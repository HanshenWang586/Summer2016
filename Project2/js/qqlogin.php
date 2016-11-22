<? php
require_once 'function.php';
require_once 'Connect2.1/qqConnectAPI.php';

$ouath = new Oauth();
$oauth -> qq_login();

?>