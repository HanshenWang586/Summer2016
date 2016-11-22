<?php
class KMLDocument
{
	function __construct()
	{
	$this->content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<kml xmlns=\"http://www.opengis.net/kml/2.2\">
<Document>";
	}
	
	function addPlacemark($placemark)
	{
	$this->content .= $placemark->output();
	}
	
	function addLineString($linestring)
	{
	$this->content .= $linestring->output();
	}
	
	function addPolygon($polygon)
	{
	$this->content .= $polygon->output();
	}
	
	function addIcon($name, $url)
	{
	$this->content .=  "<Style id=\"$name\">
						<IconStyle id=\"$name\">
						<Icon>
						<href>$url</href>
						</Icon>
						</IconStyle>
						</Style>";
	}
	
	function output()
	{
	$this->content .= "</Document></kml>";
	return $this->content;
	}
}
?>