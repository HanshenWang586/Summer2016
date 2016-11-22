	<?php
class CacheTools extends CMS_Class {
	public function init($args) {
		if(!extension_loaded('apc'))
			throw new Exception($this->lang('E_APC_NOT_LOADED'));
	}

	/**
	 * Retrieves a value from cache with a specified key or array of keys
	 */
	public function get($key) {
		// Respect no-cache headers.
		//if (array_key_exists('HTTP_CACHE_CONTROL', $_SERVER) and $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache') return NULL;
		$key = sprintf('/%s/%s', $this->model->lang, $key);
		return apc_fetch($key);
	}

	/**
	 * Stores a value identified by a key in cache.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($key,$value,$expire) {
		$key = sprintf('/%s/%s', $this->model->lang, $key);
		return apc_store($key,$value,$expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($key,$value,$expire) {
		$key = sprintf('/%s/%s', $this->model->lang, $key);
		return apc_add($key,$value,$expire);
	}

	/**
	 * Deletes a value with the specified key from cache
	 *
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($key) {
		$key = sprintf('/%s/%s', $this->model->lang, $key);
		return apc_delete($key);
	}

	/**
	 * Deletes all values from cache.
	 * @return boolean whether the flush operation was successful.
	 */
	public function flush() {
		return apc_clear_cache('user');
	}
}
