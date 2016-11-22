$(function() {
	var radio = $('input[name=type]'),
		form = radio.parents('form:first'),
		event_date = form.find('#event_date').parent(),
		starting_time = form.find('#starting_time').parent(),
		end_date = form.find('#end_date').parent(),
		days = form.find('.checkboxGroup').parent();
	
	function disable(group) {
		group.hide('fast').find('input').prop('disabled', true);
	}
	
	function enable(group) {
		group.show('fast').find('input').prop('disabled', false);
	}
	
	radio.change(function() {
		var $this = $(this),
			val = $this.val();
		if (val == 'one-day') {
			disable(end_date); disable(days);
			enable(event_date);
		} else if (val == 'multiple-days') {
			disable(days);
			enable(end_date); enable(event_date);
		} else if (val == 'weekly') {
			disable(event_date); disable(end_date);
			enable(days);
		}
	}).filter(':checked').trigger('change');
	$('#all_day').change(function() {
		if ($(this).is(':checked')) disable(starting_time);
		else enable(starting_time);
	}).trigger('change');
});

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

function calendarSuggestLocations() {
	$('#suggested_locations').hide();
	
	var location = $('#location').val();
	
	if (location.length > 3) {
		$('#suggested_locations_loading').show();
		$('#suggested_locations').load('/en/calendar/findlocations/',
									   {location_stub: location},
									   function() {
											$('#suggested_locations_loading').hide();
											$('#suggested_locations').show();
										});
	}
}

function calendarUseSuggestedLocation(location_id) {
	$('#form_calendar_item_submit').show();
	$('#suggested_locations').empty();
	$('#selected_location').load('/en/calendar/locations/',
									{location_id: location_id});
}

function calendarLoadSuggester() {
	$('#form_calendar_item_submit').hide();
	$('#selected_location').load('/en/calendar/suggest/');
}