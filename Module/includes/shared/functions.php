<?php

function addHttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}

function unixToDatetime($timestamp = false) {
	if (!$timestamp or !is_numeric($timestamp)) $timestamp = time();
	return date("Y-m-d H:i:s", $timestamp);
}

function unixToDate($timestamp = false) {
	if (!$timestamp or !is_numeric($timestamp)) $timestamp = time();
	return date("Y-m-d", $timestamp);
}

function summarizeWords($text, $maxWords = 15) {
	if (!is_numeric($maxWords) or !$maxWords > 1) $maxWords = 15;
	preg_match('/^([^.!?\s]*[\.!?\s]*){0,' . $maxWords . '}/', trim(strip_tags($text)), $abstract);
	$result = $abstract[0];
	if ($result != $text) $result .= ' &hellip;';
	return $result;
}

function validateEmail($email) {
	$val = true;
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $val = false;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex) $val = false;
	else {
		$domain = substr($email, $atIndex+1);
		$local = substr($email, 0, $atIndex);
		$localLen = strlen($local);
		$domainLen = strlen($domain);
		if ($localLen < 1 || $localLen > 64) $val = false;
		else if ($domainLen < 1 || $domainLen > 255) $val = false;
		else if ($local[0] == '.' || $local[$localLen-1] == '.') $val = false;
		else if (preg_match('/\\.\\./', $local)) $val = false;
		else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) $val = false;
		else if (preg_match('/\\.\\./', $domain)) $val = false;
		else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
		 // character not valid in local part unless 
		 // local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) $val = false;
		}
	}
	return $val;	
}

function formatMoney($number, $fractional=false) {
    if ($fractional) {
        $number = sprintf('%.2f', $number);
    }
    while (true) {
        $replaced = preg_replace('/(-?\d+)(\d\d\d)/', '$1,$2', $number);
        if ($replaced != $number) {
            $number = $replaced;
        } else {
            break;
        }
    }
    return $number;
}

function ifElse(&$var, $alt) {
	if (request($var)) return $var;
	else return $alt;
}

function ifNot(&$mixed, $alt, $field = false) {
	if ($field and !request($mixed[$field])) $mixed[$field] = $alt;
	elseif (!request($mixed)) $mixed = $alt;
	return $mixed;
}

function clipDimension($clipX, $clipY, &$x, &$y) {
	$ratio = min($clipX / $x, $clipY / $y);
	return array((int) round($x * $ratio), (int) round($y * $ratio));
}

function JSONOut($data) {
	$output = strpos($_SERVER['HTTP_ACCEPT'], 'application/json') > -1 ? 'json' : 'html';
	$json = json_encode($data);
	if ($output == 'json') {
		header('Content-Type: application/json; charset=utf-8');
		echo $json;
	} else {
		header('Content-Type: text/html; charset=utf-8');
		printf("<textarea style=\"width: 700px; height: 300px;\">%s</textarea>", $json);
	}
	die();
}

function parseInt($string) {
	return (int) $string;
}

// Replace vars in the form of [keyname] inside the body
function replaceVars($content, $replaceList, $prepend = '', $sanitise = false) {
	if ($replaceList && $content) {
		foreach ($replaceList as $key => $value) {
			if ($sanitise) $value = htmlentities($value);
			$content = str_replace('[' . $prepend . $key . ']', $value, $content);
		}
	}
	return $content;
}

function randomString($md5 = false, $length = 12) {
	$hash = "abcdefghijklmnopqrstuvwxyz0123456789";
	$strlength = strlen($hash);
	$string = "";
	for ($i = 0; $i < $length; $i++) $string .= $hash[rand(0, $strlength) - 1];
	return $md5 ? md5($string) : $string;
}

function stripslashes_r($value) {
	return is_array($value) ? array_map("stripslashes_r", $value) : stripslashes($value);
}

function request(&$var) {
	return (isset($var) ? $var : false);
}

/*
 *	returns the variable given as parameter if this variable is set, otherwise returns the empty string.
 *
 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
 */
function sprintif(&$var) {
	return (request($var) ? $var : "");
}

/*
 *	prints the variable given as parameter if this variable is set, otherwise prints the empty string.
 *
 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
 */
function printif(&$var) {
	return printf("%s", request($var));
}

/*
 *	Returns the given boolean as a string.
 *
 *	@param boolean		(Boolean)		The boolean to print.
 *
 *	@return result		(String)		Returns the boolean as a string.
 *
 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
 */
function sprintBool(&$boolean) {
	return sprintf(request($boolean) ? "true" : "false");
}

/*
 *	Prints the given boolean as a string.
 *
 *	@param boolean		(Boolean)		The boolean to print.
 *
 *	@return result		(String)		Returns the boolean as a string.
 *
 *	@Author 							Yereth Jansen (yereth@wharfinternet.nl)
 */
function printBool(&$boolean) {
	return printf(request($boolean) ? "true" : "false");
}

function parseURL($url = false, $noEnv = false) {
	if (!$url && !$noEnv) {
		$url = $_SERVER['REQUEST_URI'];
	}
	return parse_url($url);
}

function getArgs($url = false, $noEnv = false) {
	// Sometimes parse_url thinks the url is not valid when only the query part is given.
	// Adding http://anything.you.want/ to the url solves this problem.
	// Since only the query part is needed it won't cause any problems.
	if($url && !strstr($url, 'http://') ) {
		$url = 'http://fake.com/'.$url;
	}
	$array = parseURL($url, $noEnv);
	if (isset($array['query'])) {
		return $array['query'];
	}
	return false;
}

// Returns the get arguments of an URL.
function parseString($url = false, $noEnv = false) {
	$args = getArgs($url, $noEnv);
	if ($args) {
		parse_str($args, $output);
		return $output;
	}
	return false;
}

function microtime_float() {
	list ($msec, $sec) = explode(' ', microtime());
	$microtime = (float)$msec + (float)$sec;
	return $microtime;
}

function makeSafeEntities($str, $convertTags = 0, $encoding = "") {
	if (is_array($arrOutput = $str)) {
		foreach (array_keys($arrOutput) as $key) {
			$arrOutput[$key] = makeSafeEntities($arrOutput[$key],$encoding);
		}
		return $arrOutput;
	} else if (!empty($str)) {
		$str = makeUTF8($str,$encoding);
		$str = mb_convert_encoding($str,"HTML-ENTITIES","UTF-8");
		$str = makeAmpersandEntities($str);
		if ($convertTags) $str = makeTagEntities($str);
		$str = correctIllegalEntities($str);
		return $str;
	}
}

// Convert str to UTF-8 (if not already), then convert to HTML numbered decimal entities.
// If selected, it first converts any illegal chars to safe named (and numbered) entities
// as in makeSafeEntities(). Unlike mb_convert_encoding(), mb_encode_numericentity() will
// NOT skip any already existing entities in the string, so use a regex to skip them.
function makeAllEntities($str, $useNamedEntities = 0, $encoding = "") {
	if (is_array($str)) {
		foreach ($str as $s) {
			$arrOutput[] = makeAllEntities($s,$encoding);
		}
		return $arrOutput;
	} else if (!empty($str)) {
		$str = makeUTF8($str,$encoding);
		if ($useNamedEntities) $str = mb_convert_encoding($str,"HTML-ENTITIES","UTF-8");
		$str = makeTagEntities($str,$useNamedEntities);
		// Fix backslashes so they don't screw up following mb_ereg_replace
		// Single quotes are fixed by makeTagEntities() above
		$str = mb_ereg_replace('\\\\',"&#92;", $str);
		mb_regex_encoding("UTF-8");
		$str = mb_ereg_replace("(?>(&(?:[a-z]{0,4}\w{2,3};|#\d{2,5};)))|(\S+?)",
				"'\\1'.mb_encode_numericentity('\\2',array(0x0,0x2FFFF,0,0xFFFF),'UTF-8')", $str, "ime");
		$str = correctIllegalEntities($str);
		return $str;
	}
}

// Convert common characters to named or numbered entities
function makeTagEntities($str, $useNamedEntities = 1) {
	// Note that we should use &apos; for the single quote, but IE doesn't like it
	$arrReplace = $useNamedEntities ? array('&#39;','&quot;','&lt;','&gt;') : array('&#39;','&#34;','&#60;','&#62;');
	return str_replace(array("'",'"','<','>'), $arrReplace, $str);
}

// Convert ampersands to named or numbered entities.
// Use regex to skip any that might be part of existing entities.
if (!function_exists('makeAmpersandEntities')) {
	function makeAmpersandEntities($str, $useNamedEntities = 1) {
		return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m", $useNamedEntities ? "&amp;" : "&#38;", $str);
	}
}
// Convert illegal HTML numbered entities in the range 128 - 159 to legal couterparts
function correctIllegalEntities($str) {
	$chars = array(
	128 => '&#8364;',
	130 => '&#8218;',
	131 => '&#402;',
	132 => '&#8222;',
	133 => '&#8230;',
	134 => '&#8224;',
	135 => '&#8225;',
	136 => '&#710;',
	137 => '&#8240;',
	138 => '&#352;',
	139 => '&#8249;',
	140 => '&#338;',
	142 => '&#381;',
	145 => '&#8216;',
	146 => '&#8217;',
	147 => '&#8220;',
	148 => '&#8221;',
	149 => '&#8226;',
	150 => '&#8211;',
	151 => '&#8212;',
	152 => '&#732;',
	153 => '&#8482;',
	154 => '&#353;',
	155 => '&#8250;',
	156 => '&#339;',
	158 => '&#382;',
	159 => '&#376;');
	foreach (array_keys($chars) as $num) {
		$str = str_replace("&#".$num.";", $chars[$num], $str);
	}
	return $str;
}

// Compare to native utf8_encode function, which will re-encode text that is already UTF-8
function makeUTF8($str,$encoding = "") {
	if (!empty($str)) {
		if (empty($encoding) && isUTF8($str)) $encoding = "UTF-8";
		if (empty($encoding)) $encoding = mb_detect_encoding($str,'UTF-8, ISO-8859-1');
		if (empty($encoding)) $encoding = "ISO-8859-1"; //  if charset can't be detected, default to ISO-8859-1
		return $encoding == "UTF-8" ? $str : @mb_convert_encoding($str,"UTF-8",$encoding);
	}
}

// Much simpler UTF-8-ness checker using a regular expression created by the W3C:
// Returns true if $string is valid UTF-8 and false otherwise.
// From http://w3.org/International/questions/qa-forms-utf-8.html
function isUTF8($str) {
	return preg_match('%^(?:
		[\x09\x0A\x0D\x20-\x7E]           // ASCII
		| [\xC2-\xDF][\x80-\xBF]            // non-overlong 2-byte
		| \xE0[\xA0-\xBF][\x80-\xBF]        // excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // straight 3-byte
		| \xED[\x80-\x9F][\x80-\xBF]        // excluding surrogates
		| \xF0[\x90-\xBF][\x80-\xBF]{2}     // planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3}         // planes 4-15
		| \xF4[\x80-\x8F][\x80-\xBF]{2}     // plane 16
		)*$%xs', $str);
}

function quote($string) {
	return sprintf("'%s'", $string);
}

function backquote($string) {
	return sprintf("`%s`", $string);
}

// Return bytes from PHP sizes (such as 8M for 8 megabytes)
function return_bytes($val) {
	$val = trim($val);
	$last = strtolower($val{strlen($val)-1});
	switch($last) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	return $val;
}

/*
 *	Returns the file size in a human readable format.
 */
function formatSize($bytes) {
	if ($bytes / 1048576 > 1) return round($bytes/1048576, 1) . 'Mb';
	else if ($bytes / 1024 > 1) return round($bytes/1024, 1) . 'Kb';
	else return round($bytes, 1) . 'bytes';
}
?>