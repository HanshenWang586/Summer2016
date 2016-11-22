function blogLoadRelated(blog_id) {
	var tags = $('#tags').val();

	$('#related_articles').load('ajax/related.php', {
		blog_id: blog_id,
		tags: tags
	});
}

function blogSuggestTags() {
	var id = 'suggested_tags';
	var tags = new String($('#tags').val());
	tags = tags.split(', ');
	var last_tag = tags[tags.length - 1];

	if (last_tag.length > 3) {
		hideDiv(id);
		$('#' + id).load('ajax/seek_tags.php',
						 'tag_stub=' + last_tag,
						 function() {
							showDiv(id);
							});
	}
	else
		hideDiv(id);
}

function blogUseSuggestedTag(tag) {
	var current_tags = $('#tags').val();

	current_tags = new String(current_tags);
	current_tags = current_tags.split(', ');
	current_tags[current_tags.length - 1] = tag;

	$('#tags').val(current_tags.join(', '));
	$('#tags').focus();

	hideDiv('suggested_tags');
}

function blogToggleRelated(blog_id, related_id, tags) {
	$.get('ajax/toggle_related.php', {	blog_id: blog_id,
										related_id: related_id},
		function() {
			blogLoadRelated(blog_id, tags)
		}
	)
}

function blogRecategorise(blog_id, category_id) {
	$('#item_' + blog_id).load(	'ajax/recategorise.php',
								'blog_id=' + blog_id + '&category_id=' + category_id);
}