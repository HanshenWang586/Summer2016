(function() {

$.fn.prettyCheckbox = function() {
	return this.each(function() {
		var self = $(this), data = self.data('prettyCheckbox'), label;
		if (!self.is(':checkbox') || data) return false;
		self.data('prettyCheckbox', true);
		label = self.parent('label');
		if (!label.length && self.is('[id]')) self.closest('form').find('label[for=' + self.attr('id') + ']');  
		if (!label.length) return;
		
		self.change(function() {
			var checked = self.is(':checked');
			label[checked ? 'addClass' : 'removeClass']('checked');
		});
		
		label
			.attr('tabIndex', 0)
			.keyup(function(e) {
				if (e.which == 32) {
					if (self.is('.checked')) input.removeAttr('checked');
					else input.attr('checked', 'checked');
					self.triggerHandler('change');
				}
			});
		
		self.trigger('change');
	});
};

})();