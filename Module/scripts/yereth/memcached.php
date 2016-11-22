<?

// Connection constants
define('MEMCACHED_HOST', '127.0.0.1');
define('MEMCACHED_PORT', '11211');
 
// Connection creation
$memcached = new Memcached;
$cacheAvailable = $memcached->addServer(MEMCACHED_HOST, MEMCACHED_PORT);

$result = $memcached->getAllKeys();
if (is_array($result)) {
	echo "<pre>";
	print_r($result);
	echo "</pre>";
}

if ($cacheAvailable) {
	$result = $memcached->get('result');
	if (!$result) {
		$memcached->set('result', 'word up');
		die('no cache');
	} else {
		die($result);
	}
} else die('damn');

?>