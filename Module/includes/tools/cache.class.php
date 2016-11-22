<?php

class CacheTools extends CMS_Class {
	/**
	 * @var Memcache the Memcache instance
	 */
	private $_cache=null;
	private $languagePrefix;
	
	public function init($args) {
		$cache=$this->getMemCache();
		
		$cache->addServer('localhost',11211);
		
		$lang = $this->module('lang');
		$this->languagePrefix = $lang->defaultLang == $lang->getCurrentLanguage() ? '' : 'lang:' . $lang->getCurrentLanguage() . '/';
	}

	/**
	 * @throws CException if extension isn't loaded
	 * @return Memcache|Memcached the memcache instance (or memcached if {@link useMemcached} is true) used by this component.
	 */
	public function getMemCache() {
		if ($this->_cache!==null) return $this->_cache;
		else {
			if(!extension_loaded('memcached')) $this->logL('E_MEMCACHED_NOT_LOADED');
			return $this->_cache= new Memcached;
		}
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($key) {
		return $this->_cache->get($this->languagePrefix . $key);
	}

	/**
	 * Retrieves multiple values from cache with the specified keys.
	 * @param array $keys a list of keys identifying the cached values
	 * @return array a list of cached values indexed by the keys
	 */
	public function getValues($keys) {
		if ($this->languagePrefix) foreach($keys as $i => $key) $keys[$i] = $this->languagePrefix . $key;
		return $this->_cache->getMulti($keys);
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($key,$value,$expire) {
		return $this->_cache->set($this->languagePrefix . $key,$value,$expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($key,$value,$expire) {
		return $this->_cache->add($this->languagePrefix . $key,$value,$expire);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($key) {
		return $this->_cache->delete($key, 0);
	}

	/**
	 * Deletes all values from cache.
	 * This is the implementation of the method declared in the parent class.
	 * @return boolean whether the flush operation was successful.
	 * @since 1.1.5
	 */
	public function flush() {
		return $this->_cache->flush();
	}
}