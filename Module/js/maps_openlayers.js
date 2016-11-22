//
var map, layer, markers;
var maps = new Array();
var layerset = new Array();
var size = new OpenLayers.Size(10,10);
var offset = new OpenLayers.Pixel(-size.w/2, -size.h/2);
var icon = new OpenLayers.Icon('/images/maps/markers/dot.png', size, offset);
	
function loadPublicMap(province_id) {
	//maps[0] = 'china.png'
	maps[2] = 'sichuan.png';
	maps[1] = 'yunnan.png';
	
	var envelopes = new Array();
	envelopes[1] = new OpenLayers.Bounds(10540564.2914,2249572.49395,12168611.8304,3501916.75475);
	envelopes[2] = new OpenLayers.Bounds(10651883.7822,2941811.32554,12279931.3212,4194155.58634);
	
	var options = { controls: [],
					units: 'm',
					projection: new OpenLayers.Projection('EPSG:900913')};
	map = new OpenLayers.Map('map', options);
	
	map.addLayer(new OpenLayers.Layer.Image('layer_' + province_id,
											'/images/maps/province/' + maps[province_id],
											envelopes[province_id],
											new OpenLayers.Size(650, 500)));
	map.zoomToMaxExtent();
	
	// add marker layer
	markers = new OpenLayers.Layer.Markers('Markers');
	map.addLayer(markers);
	
	// and populate with markers
	var request = OpenLayers.Request.GET({
		url: '/en/map/markers/',
		callback: addMarkers
	});
}

function featureOver(feature) {
	mapLoadProvince(feature.attributes.province_id);
}

function featureClick(feature) {
	//location.href = '/en/destination/' + feature.attributes.province_code + '/';
	mapLoadProvinceContent(feature.attributes.province_id);
}
	
function convertLonLatToGoogle(lon, lat) {
	lonlat = new OpenLayers.LonLat(lon, lat);
	lonlat.transform(new OpenLayers.Projection('EPSG:4326'), new OpenLayers.Projection('EPSG:900913'));
	return lonlat;
}

function mapLoadProvince(province_id) {
	for (x in map.layers) {
		if (x != 0 && !map.layers[x].isVector)
			map.layers[x].setVisibility(false);
	}
	map.layers[layerset[province_id]].setVisibility(true);	
}

function mapLoadProvinceContent(province_id) {
		for (y in maps) {
				$('#map_content_' + y).hide();
				$('#map_content_' + province_id).fadeIn();
		}
}







function getText(element) {
	return element.textContent ? element.textContent : element.text;
}

function addMarkers(request) {
	g =  new OpenLayers.Format.XML();
	xml = g.read(request.responseText);	
	pins = xml.getElementsByTagName('marker');

	for (i = 0; i < pins.length; i++) {
		location_id = getText(pins[i].childNodes[1]);
		name = getText(pins[i].childNodes[3]);
		lat = getText(pins[i].childNodes[5]);
		lon = getText(pins[i].childNodes[7]);
		url = getText(pins[i].childNodes[9]);
		
		html = "<div style=\"width:100%;padding: 5px;opacity:0.5;background-color:#555;\"><a href=\"" + url + "\" style=\"font-size:0.8em; color:#fff;\">" + name + "</a></div>";
		addMarker(lat, lon, html);
	}
}

function addMarker(lat, lon, html) {
	marker = new OpenLayers.Marker(convertLonLatToGoogle(lon, lat), icon.clone());
	marker.popup = new OpenLayers.Popup(	null,
											convertLonLatToGoogle(lon, lat),
											new OpenLayers.Size(200, 50),
											html,
											true);
	marker.popup.autoSize = true;
	marker.popup.panMapIfOutOfView  = true;
	marker.popup.hide();

	var markerClick = function (evt) {
		map.addPopup(this.popup, true);
		this.popup.toggle();
	};
	
	marker.events.register('mouseover', marker, markerClick);
	markers.addMarker(marker);
}