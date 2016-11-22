<?php
/**************************************************************************/
function debug($array)
{
$content = '<pre>';
$content .= print_r($array, true);
$content .= '</pre>';
return $content;
}
?>