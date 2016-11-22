(function() {

$(init);

function processCategories(context) {
	context = $(context || this);
	//context.find('ul.categorySelect li:not(.jq_hover)').addClass('jq_hover').hoverClass('hover');
}

function init() {
	processCategories('body');
	
	var selects = $('#categorySelect select');
	
	function checkSelects(e) {
		var submit = true;
		selects.each(function() {
			var self = $(this);
			if (self.has('option') && !self.val()) {
				if (e) self.focus();
				submit = false;
			}
		});
		return submit;
	}
	
	var form = $('#categorySelectForm').submit(checkSelects);
	
	var timeout;
	selects.change(function() {
		clearTimeout(timeout);
		setTimeout(function() {
			selects.eq(0).closest('form').find(':submit').fadeTo(300, checkSelects() ? 1 : 0);
		}, 100);
	});
	
	selects.eq(0).closest('form').find(':submit').fadeTo(300, checkSelects() ? 1 : 0);
	
}

})();