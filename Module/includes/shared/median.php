<?php
function median($values) {
	sort($values);
	$index = count($values)/2;
	
	if (is_int($index))
		return $values[$index];
	else {
		$index_1 = $index - 0.5;
		$index_2 = $index + 0.5;
		return ($values[$index_1] + $values[$index_2]) / 2;
	}
}
?>