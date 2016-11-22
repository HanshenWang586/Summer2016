(function() {

$(init);

function init() {
	processLang('body');
	
	var form = $('#langEditorSelect');
	if (form.length) {
		form.get(0).reset();
		form.ajaxifyForm({target: form.next(), args: {action: 'getTable'}, onLoad: processLang});
	}
}

function processLang(context) {
	$(context).langEditable();
	// The remove button should do an ajax request...
	$(context).find('a.langRemove').ajaxLoad({output: 'json'}, function(json) {
		if (json.result) $(this).closest('tr').fadeOut(function() { $(this).remove(); });
	});
}

$.fn.langEditable = function() {
	var lang, args = {output: 'json', m: 'lang', action: 'save', data: false}, els = this.find('span.lang_m:not(.jq_lang)');
	els.each(function(i) {
		var $this = $(this), selectIndex = false;
		$this.addClass('jq_lang');
		setTimeout(function() {
			$this.hoverClass('lang_hover');
			var data = $this.data();
			if (!data.lang) data.lang = $('html').attr('lang');
			
			function submitValue(value, settings) {
				data['value'] = value;
				args.data = data;
				var url = $.makeURL(CMS_URL.langRoot, args);
				$.getJSON(url, function(json) {
					if (json.result) $this.removeClass('lang_default lang_empty lang_empty');
					if (selectIndex && !isNaN(selectIndex) && (selectIndex > -1)) {
						els.eq(selectIndex).click();
						selectIndex = false;
					}
				});
				return value;
			}
			
			$this.editable(submitValue, {
				tooltip		: 'Click to edit',
				indicator	: 'Saving...',
				onkeydown	: function(e, settings, el) {
					// If tabkey pressed, go to next or prev
					if (e.which === 9) {
						$(el).find('form').submit();
						selectIndex = e.shiftKey ? i - 1 : i + 1;
					}
				}
			});
		},0);
	});
	return this;
}

$.fn.ajaxifyForm = function(options) {
	options = options || {};
	function show(toShow, toHide) {
		toHide.slideUp('easeInOutCubic', function() {
			toShow.slideDown('easeInOutCubic');
		});
	}
	
	this.filter('form:not(.jq_ajaxForm)').addClass('jq_ajaxForm').each(function() {
		var form = $(this), map = {}, active = form.serialize(), stack = [];
		map[active] = options.target;
		form.find(':submit, :image').css('display', 'none');
		
		var args = $.extend({output: 'json'}, options.args || {});
		form.args(args).ajaxForm({dataType: 'json', beforeSubmit: function() {
			var current = form.serialize();
			if (map[current]) {
				show(map[current], map[active]);
				active = current;
				return false;
			} else stack.unshift(current);
		}, success: function(json) {
			var current = stack.pop();
			map[current] = $(json.result).insertAfter(map[active]).hide();
			if (options.onLoad) options.onLoad(map[current]);
			show(map[current], map[active]);
			active = current;
		}});
		form.find('select').change(function() {
			form.submit();
		});
	});
	return this;
}

})();