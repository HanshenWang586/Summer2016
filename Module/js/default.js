function paginateResults(ss, type, page) {
	if (page != 0) {
		$('#' + type + '_results').fadeTo('fast', .5);
		$('#' + type + '_results').load('/en/search/type/' + type + '/' + encodeURIComponent(ss) + '/' + page,
				function() {$('#' + type + '_results').fadeTo('fast', 1);});
	}
}

function loadEventsByDate(date) {
	$('#summary_events').load('/en/calendar/date/' + date + '/');
}

function runSearch(type, ss) {
	$('#' + type + '_results').load('/en/search/type/' + type + '/' + encodeURIComponent(ss));
}

function findPrivateMailRecipients(ss) {
	if (ss.length > 2) {
		$('#pmrecipients_results').load('/en/users/pm_recipients/' + encodeURIComponent(ss),
			function() {$('#pmrecipients_results').fadeIn();});
	}
	else
		$('#pmrecipients_results').hide();
}

function findPrivateMailUsers(ss) {
	if (ss.length > 2) {
		$('#pmusers_results').load('/en/users/pm_users/' + encodeURIComponent(ss),
			function() {$('#pmusers_results').fadeIn();});
	}
	else
		$('#pmusers_results').hide();
}

function addPrivateMailRecipient(user_id, nickname) {
	$('#to').val(nickname).attr('disabled', true);
	$('#pmrecipients_results').fadeOut();
	$('#pm_compose').append('<input type="hidden" name="to_id" value="' + user_id + '" />');
}

var listingsSearchTimeout;
var lastSearch = {};
function processListingsSearch(id, city_id, ss) {
	if (ss.length > 2 && ss !== lastSearch[id]) {
		lastSearch[id] = ss;
		if (listingsSearchTimeout) clearTimeout(listingsSearchTimeout);
		listingsSearchTimeout = setTimeout(function() {
			$('#' + id).load(	'/en/listings/search/',
							'ss=' + encodeURIComponent(ss) + '&city_id=' + city_id,
							function() {
								$('#' + id).fadeIn();
							});
		}, 300);
	}
}

function attachInfoWindow(map, marker, name, address, listing_id, url) {
	var infowindow = new google.maps.InfoWindow;
	infowindow.setContent('<span class="map_infowindow"><a href="' + url + '"><b>' + name + '</b></a><br />' + address + '</span>');
	google.maps.event.addListener(marker, 'click', function() {
		infowindow.open(map, marker);
	});
}

function loadItemMapper(dest_element, listing_id) {

	$.getJSON('/en/listings/item_json/' + listing_id + '/',
			function(item) {
				var latlon = new google.maps.LatLng(item.lat, item.lon);
				var image = new google.maps.MarkerImage('/images/gokunming/map_pin.png',
				new google.maps.Size(13, 12));
	
				var map_options = {
					zoom: 16,
					center: latlon,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					navigationControl: true,
					mapTypeControl: false,
					scaleControl: false
				}
	
				map = new google.maps.Map(document.getElementById(dest_element), map_options);
	
				var marker = new google.maps.Marker({
					position: latlon,
					map: map,
					icon: image,
					title: item.name
				});
	
				google.maps.event.addListener(map, 'click', function(event) {
					updateMarker(marker, event.latLng, listing_id);
				});
			}
	);
}

function updateMarker(marker, location, listing_id) {
    marker.setPosition(location);
    map.setCenter(location);

	$.ajax({
	type: 'GET',
	url: '/en/listings/map_suggest/' + listing_id + '/' + location.lat() + '/' + location.lng() + '/'
	});
}

function ajax_action_categorize_add(listing_id, category_id) {
	if (category_id)
		$('#listings_chosen_categories').load('/en/listings/add_category/', 'listing_id=' + listing_id + '&category_id=' + category_id);
}

function ajax_action_categorize_remove(listing_id, category_id) {
	$('#listings_chosen_categories').load('/en/listings/remove_category/', 'listing_id=' + listing_id + '&category_id=' + category_id);
}