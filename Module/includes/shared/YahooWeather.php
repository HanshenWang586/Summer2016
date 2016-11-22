<?php
$url = "http://xml.weather.yahoo.com/forecastrss?p=CHXX0076&u=c";
$xmlParser = xml_parser_create();
xml_set_element_handler( $xmlParser, 'startElement', 'endElement' );
xml_set_character_data_handler( $xmlParser, 'charElement' );

$fp = fopen($url, 'r');

	while( $data = fread( $fp, 4096 ) )
	{
	xml_parse( $xmlParser, $data, feof( $fp ) );
	}

fclose( $fp );
xml_parser_free( $xmlParser );




function startElement( $parser, $tagName, $attrs )
{
echo "<". strtolower( $tagName ) . ">";
}

function endElement( $parser, $tagName )
{
echo "</" . strtolower( $tagName ) .">";
}

function charElement( $parser, $text )
{
echo "$text";
}
?>