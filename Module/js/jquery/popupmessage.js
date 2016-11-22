;var PopupMessage = function() {
	// If the context is global, return a new object, if we don't already have a views object. (1 views only)
	if ( window == this ) {
		return window.___popupMessage ? window.___popupMessage : new PopupMessage();
	}
	
	window.___popupMessage = this;

	$("<div id=\"dimmer\" title=\"Click to close\" style=\"cursor: pointer; position: absolute; left: 0; top: 0; width: 100%; background: white; z-index:100; display:none;\"></div>")
		.appendTo('body')
		.click(function() {
			PopupMessage().hide();
		});
};

PopupMessage.prototype = {
	settings: {
		emptyOnHide: false,
		width: 600,
		ifixpng: 'img',
		lightboxButton: "assets/icons/close.png",
		addHelpers: false,
		easing: 'easeOutCubic'
	},
	
	set: function(settings) {
		$.extend(this.settings, settings);
	},
	
	lightbox: function(content, options) {
		options = options || {};
		var $$ = $('#lightbox'), self = this;
		if (!($$.length)) {
			$$ = '<div id="lightbox"><div id="lightboxInner">' +
				'<span id="lightboxCloseLink">' +
					'<span id="lightboxCloseCaption">close</span>' +
					'<img alt="sluiten" src="' + this.settings.lightboxButton + '" id="lightboxClose"/>' +
				'</span>';
			if (this.settings.addHelpers) $$ = $$ + '<span class="top">&nbsp;</span><span class="tl">&nbsp;</span><span class="tr">&nbsp;</span>';
			$$ = $$ + '<div id="lightboxContent"></div>';
			if (this.settings.addHelpers) $$ = $$ + '<span class="bottom">&nbsp;</span><span class="bl">&nbsp;</span><span class="br">&nbsp;</span></div></div>';
			$$ = $($$).css({textAlign: 'center', position: 'absolute', left: 0, display: 'none', width: '100%'}).appendTo('body');
			$('#lightboxCloseLink', $$).css({cursor: 'pointer', zIndex: 1001}).click(function() { self.hide(); });
			if (this.settings.shadow && $.shadow) {
				this.settings.emptyOnHide = '.jq_shadow_inner';
				var c = $('#lightboxContent').html('<div/>').shadow($.extend({callback: helper}, this.settings.shadow));
			} else helper();
		} else helper();
		
		function helper() {
			var css = {
				width: self.settings.width,
				margin: 'auto',
				textAlign: 'left',
				position: 'relative'
			};
			
			css.width = options.width || css.width;
			$('#lightboxInner', $$).css(css);
			
			if (!self.settings.emptyOnHide || self.settings.emptyOnHide === true) self.settings.emptyOnHide = '#lightboxContent';
			
			if (content) self.settings.contentEl = $$.find(self.settings.emptyOnHide).html(content);
			
			self.show($$, false, options);
		}
	},
	
	// helper function to show a message (html) inside a 'lightbox'
	showMessage: function(message, options) {
		options = options || {};
		var $$ = $('#myMessageBoxHelper');
		if (!($$.length)) {
			$$ = $('<div id="myMessageBoxHelper" style="position: absolute; width: 100%; display: none; text-align: center; left: 0;"><div id="myMessageBox" style="margin: auto; position: relative;"></div></div>').appendTo('body');
		};
		var d = {width: '', height: ''};
		if ($.metadata) {
			var data = $(message).metadata();
			d.width = data.width || d.width;
			d.height = data.height || d.height;
		}
		$$.find(':first-child').css(d).html(message);
		this.show('myMessageBoxHelper', options.onShow, options);
	},
	
	show: function(control, callback, options) {
		options = options || {};
		var self = this;
		var temp = typeof control == 'string' ? $('#' + control) : $(control);
		if (!temp.length) return;
		else this.control = temp;
		$('body').children().not(this.control).find('embed, object, select').css({ 'visibility' : 'hidden'});
		
		if (callback) options.onShow = callback;
		if ($('#dimmer').is(':hidden')) {
			$('#dimmer').css({opacity: 0.7, zIndex: 900}).animate({opacity: 'show'}, 200, function() {
				self._show(temp, options);
			});
		} else this._show(temp, options);
		return this;
	},
	
	_show: function(control, options) {
		var self = this;
		this._setHeight();
		if ($.ifixpng && this.settings.ifixpng) $(this.settings.ifixpng === true ? 'img' : this.settings.ifixpng, control).ifixpng();
		control.css({zIndex: 1000, position: 'absolute', opacity: 0, display: 'block'}).animate({opacity: 1}, 200, function() {
			// Remove the opacity property in IE, as it messes with the overflow
			var timeout;
			$(window).bind('resize.popupmessage scroll.popupmessage', function(e) {
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					window.___popupMessage._setHeight(e);
				}, 20);
			});
			$(document).bind('keyup.popupmessage', function(e) {
				var prevent = true;
				if (e.keyCode == 27) self.hide();
				else prevent = false;
				// If we don't do this, we stop the animated GIF as well
				if (prevent) e.preventDefault();
			});
			if ($.isFunction(options.onShow)) options.onShow.apply(control);
		});
		if ($.isFunction(options.onHide)) control.one('hide.popupmessage', options.onHide);
	},
	
	hide: function(e) {
		var self = this;
		$(window).add(document).unbind('.popupmessage');
		var $$ = $(this.control).stop().animate({opacity: 0}, function() {
			$$.hide();
			if (self.settings.emptyOnHide) {
				$$.find(self.settings.emptyOnHide.constructor != Boolean ? self.settings.emptyOnHide : ':first-child').empty();
			}
			
			$('#dimmer').animate({opacity: 'hide'}, 300, function() {
				$('embed, object, select').css({ 'visibility' : 'visible'});
			});
		}).trigger('hide');
		
		return this;
	},
	
	_setHeight: function(e) {
		var control = $(window.___popupMessage.control);
		var $win = $(window);
		var scrolltop = $win.scrollTop();
		var location = $win.scrollTop() + ($win.height() / 2) - (control.height() / 2);
		if (location > scrolltop) {
			if (e) control.stop();
			control.animate({top: location}, 'fast', window.___popupMessage.settings.easing);
		}
		else if (!e) control.animate({top: scrolltop + 25}, 'fast', window.___popupMessage.settings.easing);
		$('#dimmer').height($(document).height());
	}
};
