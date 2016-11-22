(function($, undefined){
	
	$(init);

	function init() {
		var selects = $('select'), cat1 = selects.filter('.selectCategory1'), data = window.categories, sel = $(cat1);
		$.template('categorySelect', '<option value="${id}">${langName}</option>');
		if (cat1.length && data) {
			var cat2 = selects.filter('.selectCategory2');
			if (cat2.length) {
				sel = sel.add(cat2);
				var cat3 = selects.filter('.selectCategory3'), catData, tempData;
				cat1.bind('change.categories', function(e) {
					if (cat1.val()) {
						cat1.find(':first-child[value=""]').remove();
						cat2.trigger('reloadData', cat1.val());
					} else cat2.empty().css('visibility', 'hidden').enable(false);
				});
				if (!cat1.val()) {
					cat2.css('visibility', 'hidden').enable(false);
				}
				if (cat3.length) {
					sel = sel.add(cat3);
					cat2.bind('change.categories', function(e) {
						if (cat2.val()) {
							cat2.find(':first-child[value=""]').remove();
							cat3.trigger('reloadData', cat2.val());
						} else cat3.empty().css('visibility', 'hidden').enable(false);
					});
					if (!cat2.val()) {
						cat3.css('visibility', 'hidden').enable(false);
					}
				}
			}
			sel.bind('reloadData', function(e, which) {
				if (!which) return;
				var self = $(this),
					catData = data[which],
					tempData;
				if (catData) {
					tempData = $.tmpl('categorySelect', catData);
					// Only add the empty option if there's more than one options in the list
					if ((catData.length > 1) && !self.is('[size]')) tempData = $.merge($.tmpl('categorySelect', {id: '', langName: ''}), tempData);
					self
						.html(tempData)
						.trigger('change')
						.css('visibility', 'visible').enable();
				} else {
					self.empty().css('visibility', 'hidden').enable(false);
				}
			});
		}
	}
	
})(jQuery);