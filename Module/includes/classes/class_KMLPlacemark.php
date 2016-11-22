<?php
class KMLPlacemark
{
private $alt = 0;

	function __construct()
	{
	$this->id = 'placemark_'.rand(0, 99999);
	}
	
	function setStyle($style)
	{
	$this->style = $style;
	}
	
	function appendToDescription($description)
	{
	$this->description .= $description;
	}
	
	function setLatitude($lat)
	{
	$this->lat = $lat;
	}
	
	function setLongitude($lon)
	{
	$this->lon = $lon;
	}
	
	function setAltitude($alt)
	{
	$this->alt = $alt;
	}
	
	function output()
	{
	$content = "\n<Placemark id=\"$this->id\">
	".($this->style != '' ? "<styleUrl>#$this->style</styleUrl>" : '')."
".($this->description != '' ? "<description><![CDATA[{$this->description}]]></description>" : '')."
<Point><coordinates>$this->lon,$this->lat,$this->alt</coordinates></Point>
</Placemark>";

	return $content;
	}
}
?>