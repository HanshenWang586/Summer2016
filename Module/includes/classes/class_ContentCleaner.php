<?php
class ContentCleaner {
	public static function cleanPublicDisplay($content) {
		return htmlentities($content, ENT_NOQUOTES, 'UTF-8');
	}

	public static function cleanForInput($content) {
		return htmlentities($content, ENT_COMPAT, 'UTF-8');
	}

	public static function cleanAdminDisplay(&$content) {
		$content = nl2br($content);
		return $content;
	}

	public static function cleanProse($content) {
		$content = str_replace("’", "'", $content);
		$content = str_replace("‘", "'", $content);
		$content = str_replace('，', ',', $content);
		$content = str_replace(' ,', ',', $content);
		$content = str_replace('“', '"', $content);
		$content = str_replace('”', '"', $content);
		$content = preg_replace("/ +/", ' ', $content);
		$content = trim($content);
		return $content;
	}

	public static function cleanForDatabase($content) {
		$content = trim($content);
		$content = preg_replace('/ +/', ' ', $content);
		$to_replace = array('--',
							"\r",
							'’',
							'‘',
							'，',
							' ,',
							'“',
							'”',
							'（',
							'）',
							'…');
		$replacements = array(	'—',
								'',
								"'",
								"'",
								',',
								',',
								'"',
								'"',
								'(',
								')',
								'...');
		$content = str_replace($to_replace, $replacements, $content);
		return $content;
	}

	public static function cleanForDatabaseBook($content) {
		$content = trim($content);
		$content = str_replace("’", "'", $content);
		$content = str_replace("‘", "'", $content);
		$content = str_replace('，', ',', $content);
		$content = str_replace(' ,', ',', $content);
		$content = str_replace('“', '"', $content);
		$content = str_replace('”', '"', $content);
		$content = str_replace('（', '(', $content);
		$content = str_replace('）', ')', $content);
		$content = str_replace("\r", '', $content);
		$content = str_replace("\t", '', $content);
		$content = preg_replace("/ +/", ' ', $content);
		$content = str_replace(" \n", "\n", $content);
		$content = str_replace("\n ", "\n", $content);
		$content = str_replace("\n", "\n\n", $content);
		$content = preg_replace("/\n{3,}/", "\n\n", $content);
		return $content;
	}

	public static function removeSpaces(&$content) {
		$content = str_replace(' ', '', $content);
		return $content;
	}

	public static function wrapChinese($content) {
		return preg_replace('/[\p{Lo}\p{Mn}]+/u', "<span lang=\"cn\" class=\"chinese\">\\0</span>", $content);
	}

	public static function linkURLs($content) {
		$content = preg_replace_callback("/http:\/\/[^\s<]+/", function ($match) {
			$match = $match[0];
			$url_parts = parse_url($match);
			
			if (strlen($match) > 70) $url_text = $url_parts['host'] . "/[...]";
			else {
				$parts = explode('://', $match);
				array_shift($parts);
				$url_text = implode('://', $parts);
			}
			
			if (!strpos($url_parts['host'], $GLOBALS['model']->module('preferences')->get('url')) >= 0) $target = ' rel="nofollow" target="_blank"';
			else $target = '';
			
			$match_o = preg_replace_callback('/\p{Lo}+/u', 'urlencode', $match);
			
			return sprintf('<a href="%s"%s>%s</a>', $match_o, $target, $url_text);
			$content = str_replace($match, "<a href=\"$match_o\"$target>$url_text</a>", $content);
		}, $content);
		
		return $content;
	}

	public static function linkHashURLs($content, $override_local = false) {
		preg_match_all("/#.+?#.+?#/", $content, $matches);

		foreach($matches[0] as $match) {
			$target = '';
			$match_ip = explode('#', trim($match, '#'));
			$match_ip[1] = trim($match_ip[1]);
			if ((strpos($match_ip[1], 'http://') === 0 && strpos($match_ip[1], $GLOBALS['model']->module('preferences')->get('url')) >= 0) || $override_local)
				$target = ' rel="nofollow" target="_blank"';
			else
				$match_ip[1] = str_replace($GLOBALS['model']->module('preferences')->get('url'), '', $match_ip[1]);
			$link = "<a href=\"".trim($match_ip[1])."\"$target>".trim($match_ip[0])."</a>";
			$content = str_replace($match, $link, $content);
		}

		return $content;
	}

	public static function processForURL($content) {
		$content = strip_tags($content);
		$content = str_replace(array(' ', '-'), array('_', '_'), strtolower($content));
		$content = preg_replace("/[^a-z0-9_]/", '', $content);
		$content = preg_replace("/_+/", '_', trim($content, '_'));
		return urlencode($content);
	}

	public static function PWrap($content) {
		$content = str_replace("\r", '', $content); // just in case :)
		$content = '<p>'.preg_replace("/[\n ]{2,}/", '</p><p>', $content).'</p>';
		return nl2br($content);
	}

	public static function unPWrap($content) {
		$content = str_replace('<p>', '', $content);
		$content = str_replace('</p>', "\n\n", $content);
		return trim($content);
	}
	
	public static function wrapArrayInTr(&$data, $tr_class = '') {
		foreach ($data as $datum) {
			$tds[] = '<td>'.$datum.'</td>';
		}
		
		$content = '<tr';
		if ($tr_class != '')
			$content .= ' class="'.$tr_class.'"';
		$content .= '>'.implode('', $tds).'</tr>';
		return $content;
	}
	
	public static function wrapArrayInTh(&$data) {
		foreach ($data as $datum) {
			$content .= '<th>'.$datum.'</th>';
		}
		
		return '<tr>'.$content.'</tr>';
	}

	public static function buildGetString($array) {
		foreach ($array as $key => $value) {
			if ($value != '')
				$getstring_bits[] = $key.'='.$value;
		}

		if (count($getstring_bits))
			return '?'.implode('&', $getstring_bits);
	}
	
	public static function hasHTML($content) {
		if ($content != strip_tags($content))
			return true;
		else
			return false;
	}
}
?>
