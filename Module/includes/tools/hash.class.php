<?php
/**
 * Secure password hashing class
 * @package secure_hash
 * @author Julius Beckmann 
 * @link http://juliusbeckmann.de/classes/secure_hash/
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @filesource
 */
/**
 * Secure password hashing class. A simple and easy to use class for secure 
 * password hashing. It is using random Salts and multiple hashing with random 
 * iterations. The class can be easily extended with own hashing methods.
 * @name Secure password hashing class
 * @version v0.1_2009.09.18
 * @access public
 * @package secure_hash
 *      
 *      Copyright 2009 Julius Beckmann
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */
class HashTools extends CMS_Class {
	
	/**
	 * Default hashing method
	 * Check available _hash_* methods
	 * @access public
	 * @var string
	 */
	var $hashing_method = 'sha1';

	/**
	 * Delimiter used in formated hash
	 * @access public
	 * @var string
	 */
	var $delimiter = '$';

	/**
	 * Length of salt
	 * @access public
	 * @var int
	 */
	var $salt_len = 32;
	
	/**
	 * Salt chars.
	 * Just using special latin chars here so we wont break charset and avoid 
	 * creating not secure salts. NO " and ' to avoid breaking queries.
	 * @access public
	 * @var string
	 */
	var $salt_chars = '^!%&/()=?+~#,.-;:_|<>@$';
	
	/**
	 * Global salt which is NOT stored inside formated hash 
	 * but needed to check integrity of hash.
	 * This is a further security method to avoid hashes being cracked.
	 * Idea is to save salt in database, as well as in a config file or just here. 
	 * A attacker would need access to both to gain enough data to crack these 
	 * hashes. Changing this value leads to incorrect hashes. 
	 * Just define it once and keep it.
	 * @access public
	 * @var string
	 */
	var $salt_global = '';

	/**
	 * Min hashing iterations
	 * Keep this at least at 10 to keep hashlength static
	 * @access public
	 * @var int
	 */
	var $iter_min = 10;

	/**
	 * Max hashing iterations
	 * Keep this under 100 to keep hashlength static
	 * @access public
	 * @var int
	 */
	var $iter_max = 99;
	
	/**
	 * Flag if permutations should be used
	 * @access public
	 * @var bool
	 */
	var $permutate = true;
	
	/**
	 * Rules for permutation.
	 * Add more rules to make hashing even more complex.
	 * Every rule gets applied
	 * @access public
	 * @var array
	 */
	var $permutations = array('0123456789abcdef|e875d60c4a2f1b93',
								'0123456789abcdef|7d15b9f3e60a82c4',
								'0123456789abcdef|b294cd1e6a038f57');
														
	public function init($args) {
		
	}
	
	// --- PUBLIC Functions ---	
	
	/**
	 * Hashes a string and returns the formated hash.
	 * @access public
	 * @param string $pass string to hash
	 * @param string $salt salt to use, default false
	 * @param integer $iter iterations to do, default false
	 * @return string hashed string
	 */
	function hash($pass, $salt=false, $iter=false) {
		
		// Generate salt if unspecified
		if($salt === false)
			$salt = $this->_salt();
			
		// Random iterations if unspecified
		if($iter === false)
			$iter = rand($this->iter_min, $this->iter_max);
		
		// Check hashing function
		$method = '_hash_'.$this->hashing_method;
		if(!method_exists($this, $method))
			die('ERROR: The hashing-method "'.$method.'" is NOT callable!');
		
		// Generate hash
		$hash = $this->$method($pass, $salt.$this->salt_global, $iter);
		
		// Return formated hash
		$d = $this->delimiter;
		return $d.$this->hashing_method.$d.$salt.$d.$iter.$d.$hash.$d;
	}

	/**
	 * Checks if a formated hash is equal to a password
	 * @access public
	 * @param string $hash_formated hash to use
	 * @param string $pass password to check
	 * @return boolean true is hash == pass
	 */
	function check($hash_formated, $pass) {
		$ret = false;
		// Check if first and last char of formated string are our delimiter
		$len = strlen($hash_formated);
		if($len > 1 && $hash_formated[0] == $this->delimiter 
				&& $hash_formated[$len-1] == $this->delimiter) {
					
			// Parse formated hash
			list(,$hashmethod, $salt, $iter, $hash) 
				= explode($this->delimiter, $hash_formated);
			
			// Check hashing function
			$method = '_hash_'.$hashmethod;
			if(!method_exists($this, $method))
				die('ERROR: The hashing-method "'.$method.'" is NOT callable!');
				
			// Check hash
			$ret = ($hash == $this->$method($pass, $salt.$this->salt_global, $iter));
		}
		return $ret;
	}

	/**
	 * Returns a new permutaion which can be used in this class
	 * Permutation format is abc|cab - a->c && b->a && c->b
	 * Default base is 0..f	 * 
	 * @access public
	 * @param $b string base for permutation
	 * @return string permutation for this class
	 */
	function _new_permutation($b=false) {
		if($b===false)
			$b = implode('', range(0,9)).implode('', range('a','f'));
		return $b.'|'.str_shuffle($b);	
	}

	// --- PRIVATE Functions ---
	
	/**
	 * Generate a random salt
	 * @access private
	 * @return string
	 */
	function _salt() {
		// Remove delimiter from salt chars
		$chars = str_ireplace($this->delimiter,'', $this->salt_chars);
		$char_count = strlen($chars)-1;

		// Generate salt
		$i = 0;
		$salt = '';
		while($i++ < $this->salt_len)
			$salt .= $chars[rand(0, $char_count)];
		
		return $salt;
	}
	
	/**
	 * Plugin sha1 hashing method
	 * @access private
	 * @param string $str string to hash
	 * @param string $salt salt to use
	 * @param int $iter iterations to do
	 * @return string 
	 */
	function _hash_sha1($str, $salt, $iter) {
		$hash = $this->_p(sha1(sha1($str).sha1($salt)));
		for($i=0; $i<$iter; ++$i)
			$hash = $this->_p(sha1(sha1($str.$hash).sha1($hash).sha1($salt.$hash)));
		return $hash;
	}
	
	/**
	 * Plugin md5 hashing method
	 * @access private
	 * @param string $str string to hash
	 * @param string $salt salt to use
	 * @param int $iter iterations to do
	 * @return string 
	 */
	function _hash_md5($str, $salt, $iter) {
		$hash = $this->_p(md5(md5($str).md5($salt)));
		for($i=0; $i<$iter; ++$i)
			$hash = $this->_p(md5(md5($str.$hash).md5($hash).md5($salt.$hash)));
		return $hash;
	}


	/**
	 * Permutation string with global rules
	 * @access private
	 * @param string string to permutate
	 * @return string permutated string
	 */
	function _p($str) {
		if($this->permutate)
			// Apply each permutation
			foreach($this->permutations as $perm) 
				$this->_permutate($str, $perm);
		return $str;
	}

	/**
	 * Permutate a string with given rule
	 * @access private
	 * @param string $str referece (faster) for the string to permutate
	 * @param string $perm rule in form of 123|312
	 * @param bool $dir direction to permutate. Default: true = forward
	 * @param integer $iter number of iterations to do. Default: depends on 
	 * 																					last char and length of $perm.
	 * @return bool always true.
	 * */
	function _permutate(&$str, &$perm, $dir=true, $iter=false) {
		// Load permutation rule
		if($dir) 
			list($from, $to) = explode('|',$perm, 2);
		else
			list($to, $from) = explode('|',$perm, 2);
		
		// Verify rule length
		$len_base = strlen($from);
		if($len_base !== strlen($to))
			return false; // Rule is incorrect - stop here.
			
		// Get a new iterationcount depending on last rule char
		if($iter === false)
			$iter = (ord($perm[strlen($perm)-1]) % ($len_base/2));
	
		// Create permutation array
		$p = array();
		for($i=0; $i<$len_base; ++$i) 
			$p[$from[$i]] = $to[$i];
			
		// Apply permutation
		$len = strlen($str);
		if($len >= 0)
			for($i=0; $i<$len; ++$i)
				if(isset($p[$str[$i]])) //This will keep chars that are not in our rule.
					$str[$i] = $p[$str[$i]];
		if($iter > 0)
			$this->_permutate($str, $perm, $dir, $iter-1);
		return true;
	}
		
}

?>
