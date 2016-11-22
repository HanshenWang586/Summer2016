<?php
class URI {

	/**
	 * @return string The whole URI
	 */
	public function getURIString() {
		return $_SERVER['PHP_SELF'];
	}

	/**
	 * @return string The host (CNAME)
	 */
	public function getHost() {
		return $_SERVER['HTTP_HOST'];
	}
}
?>