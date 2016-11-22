function forumFilterThreads(ss) {
	if (ss.length > 3) {
		$('#results').hide();
		$('#results').load('ajax/filter.php', $('#filter_threads').serialize(), function() {$('#results').show();});
	}
}