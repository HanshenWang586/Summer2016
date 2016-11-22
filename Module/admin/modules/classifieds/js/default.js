function processFormWaiting(action) {
	$.get('control.php', $('#form_waiting').serialize() + '&action=' + action, function(){window.location.reload();});
}