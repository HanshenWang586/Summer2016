<?php
function clean_gpc($variable) {
	if (!get_magic_quotes_gpc()) return $variable;
	elseif (is_array($variable)) return array_map('clean_gpc', $variable);
	else return stripslashes($variable);
}
?>