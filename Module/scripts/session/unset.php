<?php
// Initialize the session.
// If you are using session_name("something"), don't forget it now!

//$hn_parts = array_reverse(explode('.', $_SERVER['HTTP_HOST']));
//session_set_cookie_params('3600', '/', ".{$hn_parts[1]}.{$hn_parts[0]}");

session_start();

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (isset($_COOKIE[session_name()]))
{
setcookie(session_name(), '', time()-42000, '/');
}

// Finally, destroy the session.
session_destroy();
?>