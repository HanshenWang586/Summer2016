<?php
require($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');

echo session_id();
echo debug(session_get_cookie_params());
echo debug($_SESSION);
?>