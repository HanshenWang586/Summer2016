(function() {

$(init);

function processContent(context) {
	context = $(context || 'body');
	context.find('a').filter('.deleteField, .deleteOption').filter(':not(.jq_ajaxload)').addClass('jq_ajaxload').ajaxLoad({output: 'json'}, function(json) {
		if (json.result) $(this).closest('li').slideUp('fast', function() { $(this).remove(); });
	});
	
	context.find('li.option').each(function() {
		var option = $(this);
		option.find('a.deleteIcon:not(.jq_ajaxload)').addClass('jq_ajaxload').ajaxLoad({output: 'json'}, function(json) {
			if (json.result) $(this).parent().hide('fast', function() { $(this).remove(); });
		});
		option.find('span.editOption:not(.processed)').addClass('processed').click(function() {
			var self = $(this),
			li = self.closest('li'),
			form = li.find('form');
			if (!form.length) {
				form = li.closest('li.field').find('form.addFieldValueForm').clone(true).hide().appendTo(li);
				form
					.removeClass('addFieldValueForm')
					.addClass('editFieldValueForm')
					.find('input[name=action]').val('editOptionIcon')
					.end()
					.find('label:first').remove()
					.end()
					.find(':submit').val(self.attr('title'));
				$('<input>').attr({type: 'hidden', name: 'data[value]', value: li.metadata().value}).appendTo(form.children());
				$('<input>').attr({type: 'button'}).val('cancel').addClass('cancel').appendTo(form.children()).click(function(e) {
					form.slideUp();
				});
				form.find('span.bulkWrapper').remove();
			}
			form.slideDown();
		});
	});
	
	context.find('span.optionsWrapper').hide();
	context.find('span.item span.showFieldOptions').hoverClass('hover').click(function() {
		$(this).closest('li').children('span.optionsWrapper').slideToggle();
	});
	
	context.find('form.moveFieldsToGroupForm:not(.ajaxified)').addClass('ajaxified').each(function() {
		var form = $(this), radio = form.find(':radio');
		radio.change(function(e) {
			var val = $(this).val();
			form.find('select').enable(val == 'group');
			form.find('input.text').enable(val == 'new');
		}).triggerHandler('change');
		form
			.args({output: 'json', get: 'fields', args: form.prev().metadata().id})
			.ajaxForm({dataType: 'json', beforeSerialize: function() {
				var values = form.prev().find('span.item > label > input').fieldValue();
				if (values.length) {
					form.find('input.fields').val(values.join(','));
					return true;
				}
				else return false;
			}, success: function(json) {
				if (json.result) {
					var wrap = form.prev();
					form.clearForm();
					wrap.find('span.item > label > input').clearFields();
					wrap.replaceWith($(json.content).find('> div.fields'));
					processContent(form.prev());
				}
			}});
	});
	
	context.find('form.addFieldValueForm:not(.ajaxified)').addClass('ajaxified').each(function() {
		var form = $(this);
		form.args({output: 'json', get: 'fieldOptions', args: form.find('[name="data[categoryField_id]"]').val()}).ajaxForm({dataType: 'json', success: function(json) {
			if (json.result) {
				form.clearForm().prev('ul.optionsList').remove();
				processContent(form.parent().prepend(json.content));
			}
		}});
	});
	
	context.find('form.addFieldForm:not(.ajaxified)').addClass('ajaxified').each(function() {
		var form = $(this);
		form.args({output: 'json', get: 'fields', args: form.find('[name="data[category_id]"]').val()}).ajaxForm({dataType: 'json', success: function(json) {
			if (json.result) {
				form.clearForm().prev().remove();
				processContent($(json.content).insertAfter(form.prev('h3')));
			}
		}});
	}).find('select[name="data[type]"]').change(function() {
		// Here change the information the user hsa to fill out based on which field type is selected.
		var form = $(this).closest('form'),
			unit = form.find('label.unit'),
			ac = form.find('label.autocomplete'),
			val = $(this).val();
		
		// For numbers, we want to show the unit input
		if (val == 'number') unit.removeAttr('disabled').show('fast');
		else unit.attr('disabled', 'disabled').hide('fast');
		
		// For text, we want to show the autocomplete option
		if (val == 'text') ac.removeAttr('disabled').show('fast');
		else ac.attr('disabled', 'disabled').hide('fast');
	}).trigger('change');
	
	context.find('div.category > span.icon > a.deleteIcon:not(.jq_ajaxload)').addClass('jq_ajaxload').ajaxLoad({output: 'json'}, function(json) {
		if (json.result) $(this).parent().hide('fast', function() { $(this).remove(); });
	});
	
	context
		.find('form.editCategoryIconForm:not(.ajaxified)')
		.addClass('ajaxified')
		.args({output: 'json'})
		.ajaxForm({dataType: 'json', success: function(json) {
			if (json.result) $('#categoriesForm').submit();
		}});
	
	// Make anything in the processed content lang editable if it's available.
	if ($.fn.langEditable) context.langEditable();
}

function processLightbox(context) {
	context = $(context || this);
	if ($.fn.langEditable) context.langEditable();
	var forms = context.find('form.addCategoryForm').args({output: 'json'}).ajaxForm({dataType: 'json', beforeSubmit: function() { forms.find('.logListWrapper').fadeOut('fast', function() { $(this).remove(); }); }, success: function(json, success, a, form) {
		if (json.result) {
			var parent = form.find('input[name="data[category_id]"]').val(),
				lang = $('html').attr('lang').toLowerCase();
			// If there's a parent id, parse it, otherwise, the parent is the 'NULL' group (root categories)
			if (parent) parent = parseInt(parent);
			else parent = 'NULL';
			// If we have a set language and the window.categories are set, continue to update the gui
			if (lang && window.categories) {
				categories[parent] = categories[parent] || [];
				window
					.categories[parent]
					.push(
						{
							id: json.result,
							langName: form.find('input.' + lang).val()
						}
					);
				var selects = $('#categoriesForm').find('select');
				// Trigger the reload of the right select boxes
				if (parent == 'NULL') selects.eq(0).trigger('reloadData', 'NULL');
				else selects.find('option[value=' + parent + ']').parent().trigger('change');
				
				// Select the newly added item
				selects
					.find('option[value=' + json.result + ']')
					.parent()
					.val(json.result)
					.trigger('change');
				PopupMessage().hide();
			}
		} else {
			// If there's no result, add the errors
			$(json.content).insertAfter(form.find('h1')).hide().slideDown('slow');
		}
	}});
}

function init() {
	// If a module is selected, autosubmit
	$('select[name=module]').change(function() {
		$(this).closest('form').get(0).submit();
	}).closest('form').find(':submit').hide();
	
	// For the categories, we'll ajax load the editable content for the different categories
	$('#categoriesForm').each(function() {
		var form = $(this),
			container = form.closest('.textContent'),
			selects = form.find('select'),
			timeout = null,
			actions = form.find('span.actions').fadeIn().find('a');
		
		actions.filter('.addCategory').lightbox(false, {onShow: processLightbox});
		actions.filter('.removeCategory').click(function(e) {
			var self = $(this),
				option = self.closest('li').find('option:selected'),
				cat = option.text(),
				result;
			result = confirm("Are you sure you want to delete the category \"" + cat + "\" plus all its sub-categories?");
			if (result) {
				$.getJSON($.makeURL(this.href, {output: 'json'}), function(json) {
					if (json.result) {
						option.remove();
						self.closest('form').unbind('submit').submit();
					} 
				});
			}
			return false;
		});
		
		form.find(':submit').hide();
		form.args({output: 'json', get: 'categoryFields'}).ajaxForm({dataType: 'json', success: function(json) {
			if (json.content) {
				container.next('.textContent').remove();
				processContent($(json.content).insertAfter(container));
			}
		}});
		
		// Setting the actions makes the actions links have the correct ID (based on the selected one in the categories
		// select boxes.
		function setActions() {
			actions.each(function() {
				var action = $(this), val;
				if (/args=/.test(this.href) && (depends = action.metadata().depends)) {
					val = selects.filter(depends).val();
					if (!val) action.fadeOut('fast');
					else {
						action.fadeIn('fast');
						this.href = this.href.substring(0, this.href.indexOf('args=') + 5) + val;
					}
				}
			});
		}
		
		setActions();
		
		selects.bind('change', function() {
			var depends;
			clearTimeout(timeout);
			timeout = setTimeout(function() {
				setActions();
				form.submit();
			}, 100);
		});
	});
	
	processContent();
}

})();