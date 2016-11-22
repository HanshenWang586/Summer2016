function rapidSearch(text, key) {
	if (text.length > 1) {
		$('#results').load('ajax/rapid_search.php', 'text=' + text + '&key=' + key);
	}
}

function rapidSearchAssociates(text) {
	if (text.length > 1) {
		$('#associate_search_results').load('ajax/rapid_search_associates.php', 'text=' + text);
	}
}

function rapidSearchNotes(text) {
	if (text.length > 1) {
		$('#results').load('ajax/rapid_search_notes.php', 'text=' + text);
	}
}

function loadActionPanel(action, contact_id, existing_id) {
	$('#loading').show();
	$('#action_panel').hide();

	params = 'contact_id=' + contact_id + '&action=' + action + '&existing_id=' + existing_id;

	$('#action_panel').load('ajax/actions.php',
							params,
							function () {
								$('#action_panel').show();
								$('#loading').hide();
								});

}

function submitActionPanelForm(form_id) {
	contact_id = $('input[name=contact_id]').val();
	$('#action_panel').load('ajax/proc.php',
							$('#' + form_id).serializeArray(),
							function() {
								refreshContact(contact_id);
							});
}

function processDelete(action, contact_id, existing_id) {
	if (conf_del()) {
		$.get(	'ajax/delete.php',
				'existing_id=' + existing_id + '&action=' + action,
				function() {refreshContact(contact_id)});
	}
}

function refreshContact(contact_id) {
	$('#contact').load('ajax/refresh.php', 'contact_id=' + contact_id);
}

function loadAddCoord(index) {
	var indices = new Array('email', 'mobile', 'fixed');
	for (i = 0; i < indices.length; i++) {
		$('#add_coord_' + indices[i]).hide();
	}

	$('#add_coord_' + index).show();
}

function loadAddName(index) {
	var indices = new Array('givenfamily_en', 'givenfamily_zh', 'nickname_en', 'nickname_zh');
	for (i = 0; i < indices.length; i++) {
		$('#add_name_' + indices[i]).hide();
	}

	$('#add_name_' + index).show();
}

function processAddCoord(form_id) {
	$.post(	'ajax/add_coord.php',
			$('#' + form_id).serializeArray());
}

function processAddName(form_id) {
	$.post(	'ajax/add_name.php',
			$('#' + form_id).serializeArray());
}