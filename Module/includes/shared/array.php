<?php

/*
 *	Is param a decent indexed array?
 */
function is_vector(&$array) {
	$next = 0;
	if (!is_array($array)) return false;
	if (empty($array)) return false;
	foreach ($array as $k=>$v) {
		if ($k !== $next) return false;
		$next++;
	}
	return true;
}

function array_remove_keys($array, $keys = array()) {
 
	// If array is empty or not an array at all, don't bother
	// doing anything else.
	if(empty($array) || (! is_array($array))) {
		return $array;
	}
 
	// If $keys is a comma-separated list, convert to an array.
	if(is_string($keys)) {
		$keys = explode(',', $keys);
	}
 
	// At this point if $keys is not an array, we can't do anything with it.
	if(! is_array($keys)) {
		return $array;
	}
 
    // array_diff_key() expected an associative array.
    $assocKeys = array();
    foreach($keys as $key) {
        $assocKeys[$key] = true;
    }
 
    return array_diff_key($array, $assocKeys);
}

/**
 * Returns one or more arrays as print_r does but in between a <pre> tag  
 * 
 * @param array $array The array to be printed
 * @return string The html to show the arrays
 */
function sprint_rf(array $array) {
	$args = func_get_args();
	$content = '<pre>';
	foreach ($args as $arr) $content .= print_r($arr, true);
	return $content . '</pre>';
}

function print_rf(array $array) {
	echo sprint_rf($array);
}

function array_get($array, $index) {
	return is_array($array) ? (array_key_exists($index, $array) ? $array[$index] : false) : false;
}

function array_top($array) {
	return is_array($array) ? array_get($array, count($array) - 1) : false;
}

function array_get_set(array $array, array $search, $alt = false) {
	foreach ($search as $key) if (array_key_exists($key, $array) and $array[$key]) return $array[$key];
	return $alt; 
}

/**
 * Recursive strpos. Returns true or false, not the position!
 *
 * @param mixed $haystack Array or String to find the $needle in
 * @param mixed $needle Array or String of needles to find in the $haystack
 * @return Boolean
 */
function strpos_r($haystack, $needle) {
	if (is_array($haystack)) {
		foreach($haystack as $val) if ($result = strpos_r($val, $needle)) return $result;
	}
	elseif (is_array($needle)) {
		foreach($needle as $val) if ($result = strpos_r($haystack, $val)) return $result;
	}
	else return strpos($haystack, $needle) > -1;
}

// Returns an associative array with all the key = value pairs in $haystack of which the key value was in $keys
function array_select_keys(array $keys, array $haystack, $callback_function = false, array $returnResult = array()) {
	$count = count($keys);
	for ($i = 0; $i < $count; $i++) {
		$key = $keys[$i];
		if ($callback_function) {
			$haystack[$key] = call_user_func_array($callback_function, array($haystack[$key]));
		}
		$returnResult[$key] = request($haystack[$key]);
	}
	return $returnResult;
}

function array_merge_real($array1 = array(), $array2 = array()) {
	foreach($array2 as $key => $value) {
		$array1[$key] = $value;
	}
	return $array1;
}

function array_implode_map($glue, $array, $callback_function = false, $args = array()) {
	$count = count($array);
	for ($i = 0; $i < $count; $i++) {
		$value = $array[$i];
		if ($callback_function) {
			if ($args) {
				$args2 = $args;
				array_unshift($args2, $value);
			} else $args2 = array($value);
			$value = call_user_func_array($callback_function, $args2);
		}
		$result = (isset($result) ? $result . $glue	 : "") . $value;
	}
	return $result;
}

// To get an associative array from a string (like foo1=bar1&foo2=bar2 => array('foo1' => 'bar1', 'foo2' => 'bar2')
function array_explode_assoc($sep_assoc, $separator, $string) {
	if (!is_string($string)) return false;
	$array = explode($separator, $string);
	$return = array();
	$count = count($array);
	for ($i = 0; $i < $count; $i++) {
		$temp = explode($sep_assoc, $array[$i]);
		if (count($temp) == 2) $return[$temp[0]] = $temp[1];
	}
	return $return;
}

// Implode for arrays with keys.
function array_implode_assoc($glue_assoc, $glue, $array, $clean = false) {
	$result = "";
	$i = 0;
	foreach ($array as $key => $value) {
		if ($clean and !trim($value)) continue;
		if ($i > 0) $result .= $glue;
		$result .= $key . $glue_assoc . $value;
		$i++;
	}
	return $result;
}

// Results in 2 arrays, given an associative one, where keys and values are separated
function array_separate($input, &$key_results, &$value_results) {
	if (!is_array($input)) return false;
	if (!is_array($key_results)) $key_results = array();
	if (!is_array($value_results)) $value_results = array();
	foreach($input as $key => $value) {
		$key_results[] = $key;
		$value_results[] = $value;
	}
	return true;
}

/**
 * array_transpose works mostly as a traditional transpose (when only a 2 dimensional array is passed as argument),
 * except for the following convenience features:
 *
 * 1. Given a $selectKey the returned array will be a 1 dimensional vector containing from each element the $selectKey value.
 * 2. Given both a $selectKey and a $selectValue, the returned array will be of the following type: [0 => [$selectKey => $value], 1 => [...], ...]
 * 3. Given a 1 dimensional $array AND a $selectKey, a single value will be returned, if it was available in the $array.
 * 4. Given a $selectKey and TRUE for $selectValue, the new array will have its 1st dimension keys replaced with the values of the selected key
 *
 * Note that 1. and 2. only work on a 2 dimensional $array.
 *
 * @param	array	$array			The array to transpose
 * @param	mixed	$selectKey		A key to select from the transposed array
 * @param	mixed	$selectValue	Together with $selectKey, it will select $selectKey => $selectValue from the given array.
 * @return	mixed
 */
function array_transpose($array, $selectKey = false, $selectValue = false) {
	if (!is_array($array)) return false;
	$return = array();
	foreach($array as $key => $value) {
		if (!is_array($value)) {
			if ($selectKey) return request($array[$selectKey]);
			else return $array;
		}
		if ($selectKey !== false and isset($value[$selectKey])) {
			if ($selectValue === true) $return[$value[$selectKey]] = $value;
			elseif ($selectValue !== false and isset($value[$selectValue])) $return[$value[$selectKey]] = $value[$selectValue];
			elseif(request($value[$selectKey])) $return[] = $value[$selectKey];
		} else {
			foreach ($value as $key2 => $value2) {
				$return[$key2][$key] = $value2;
			}
		}
	}
	return $return;
}

function array_remove_value($val,&$arr){
	$index = array_search($val, $arr);
	if($index === false) return false;
	$arr = array_merge(array_slice($arr, 0, $index), array_slice($arr, $index + 1));
	return true;
}

?>