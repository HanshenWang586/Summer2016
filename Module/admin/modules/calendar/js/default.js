function showDay() {
	var days = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday")
	var d = new Date();
	d.setFullYear($('#yyyy').val(), $('#mm').val() - 1, $('#dd').val());
	$('#date_text').html(days[d.getDay()]);
}

function calendarSuggestLocations() {
	var location = $('#location').val();
	var location = new String(location);
	hideDiv('suggested_locations');

	if (location.length > 3) {
		showDiv('suggested_locations_loading');
		$('#suggested_locations').load('ajax/seek_locations.php',
									   'location_stub=' + location,
									   function() {
											hideDiv('suggested_locations_loading');
											showDiv('suggested_locations');
										});
	}
}

function calendarUseSuggestedLocation(location_id) {
	showDiv('form_calendar_item_submit');
	$('#suggested_locations').empty();
	$('#selected_location').load(	'ajax/location.php',
									'location_id=' + location_id,
									function() {seekEvents(location_id)});
}

function calendarLoadSuggester() {
	hideDiv('form_calendar_item_submit');
	$('#selected_location').load('ajax/suggest.php');
}

function seekEvents(listing_id) {
	$('#existing_events').empty();
	if (listing_id) {
		$('#existing_events').html('loading...');
		$('#existing_events').load(	'ajax/seek_events.php',
								   'listing_id=' + listing_id + '&yyyy=' + $('#yyyy').val() + '&mm=' + $('#mm').val() + '&dd=' + $('#dd').val());
	}
}