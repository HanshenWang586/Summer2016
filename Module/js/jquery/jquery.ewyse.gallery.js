;(function ($) {

// Change global settings
$.ewyseGallery = {
	set: function(settings) {
		$.ewyseGallery.settings = $.extend(true, $.ewyseGallery.settings, settings);
	},
	
	$: {},
	
	// The global settings
	settings: {
		imgPath: 'images/gallery/',
		img: {
			close: 'close.png',
			overview: 'overview.gif',
			info: 'info.gif',
			loader: 'loader.gif',
			prev: 'previous.gif',
			next: 'next.gif',
			play: 'play.gif',
			pause: 'pause.gif',
			sep: 'sep.gif'
		},
		slideshowInterval: 3500,
		minWidth: 300,
		minHeight: 0,
		maxWidth: 900,
		maxHeight: 600,
		borderSpace: 0,
		autoMaxDimension: false, // If true, the max width and height will be calculated through the screen size on gallery show.
		fitImage: true,
		overlayBgColor: '#000',
		overlayOpacity:	0.5,
		cacheAjaxRequests: false,
		cache: {},
		flashparams: {
			version: [9,45], /* JW player needs at least this Flash version */
			width: 400, 
			height: 320
		},
		flashvars: {autostart: true}, 
		plugins: {
			flv: 'flash',
			swf: 'flash',
			youtube: 'flash'
		}
	},
	
	data: {},
	
	current: 0,
	
	show: false, // store whether we are showing ourselves.. good for async checks
	
	timer: false, // The timer is used for the slideshow
	
	_initInterface: function(options) {
		if (!this.$['gallery']) {
			function _img(name) {
				return $.ewyseGallery.settings.imgPath + $.ewyseGallery.settings.img[name];
			};
			this.options = $.extend({appendTo: 'body', showInContent: false}, options || {});
			
			$(this.options.appendTo).append(
				(this.options.showInContent ? '' : '<div id="egallery-overlay" title="close" style="display: none;"></div>') +
				'<div id="egallery" style="display: none;">' +
					'<div id="egallery-container">' +
						(this.options.showInContent ? '' : '<a id="egallery-close-link" href="javascript:void(0)"><img id="egallery-close" src="' + _img('close') + '" alt="Close"></a>') +
						'<div id="egallery-info"><span></span></div>' +
						'<div id="egallery-loading"><img src="' + _img('loader') + '"></div>' +
						'<div id="egallery-imageContainer"><img style="display: none;" id="egallery-image" alt="image"></div>' +
						'<div id="egallery-controls">' +
							'<table><tr><td>' +
								'<img class="egallery-sep" src="' + _img('sep') + '" alt="&nbsp;">' +
								'<img id="egallery-button-prev" src="' + _img('prev') + '" alt="Previous Image" title="Previous Image">' +
								'<img id="egallery-button-play" src="' + _img('play') + '" alt="Play Slideshow" title="Play Slideshow">' +
								'<img id="egallery-button-pause" style=\"display: none;\" src="' + _img('pause') + '" alt="Pause Slideshow" title="Pause Slideshow">' +
								'<img id="egallery-button-next" src="' + _img('next') + '" alt="Next Image" title="Next Image">' +
								'<img class="egallery-sep" src="' + _img('sep') + '" alt="&nbsp;">' +
								'<img id="egallery-button-overview" src="' + _img('overview') + '" alt="View thumbnails" title="View thumbnails">' +
								'<img class="egallery-sep" src="' + _img('sep') + '" alt="&nbsp;">' +
								'<img id="egallery-button-info" src="' + _img('info') + '" alt="Toggle Info" title="Toggle Info">' +
								'<img class="egallery-sep" src="' + _img('sep') + '" alt="&nbsp;">' +
							'</td></tr></table>' +
							'<div id="egallery-index"></div>' +
						'</div>' +
					'</div>' +
				'</div>'
			);
			
			if ($.ifixpng) $('#egallery *').ifixpng();

			this.$ = {
				overlay: $('#egallery-overlay').css({
					backgroundColor:	this.settings.overlayBgColor,
					opacity:			this.settings.overlayOpacity
				}),
				info: $('#egallery-info').css({zIndex: 1002}),
				'close-link': $('#egallery-close-link').bind('click', this.actions.close),
				'b': {
					'info': $('#egallery-button-info'),
					'play': $('#egallery-button-play'),
					'pause': $('#egallery-button-pause'),
					'prev': $('#egallery-button-prev'),
					'next': $('#egallery-button-next'),
					'overview': $('#egallery-button-overview').hide()
				},
				gallery: $('#egallery'),
				loading: $('#egallery-loading').css('z-index', 2000),
				controls: $('#egallery-controls'),
				image: $('#egallery-image'),
				'image-container': $('#egallery-imageContainer').css({position: 'relative', opacity: 1}),
				container: $('#egallery-container'), // Opacity = 1 are IE hacks to let images with opacity > 0 and < 1 not 'see through' the background
				index: $('#egallery-index')
			};

			if (this.options.hideInfo) this.$['b']['info'].remove();
			this.$['controls'].find('td').css({position: 'relative', opacity: 1});
			this._setButton('overview', true);
		}

		return this.$['gallery'];
	},
	
	plugins: {
		flash: {
			create: function(img) {
				var params = {};
				
				/* l=263 requests an other YouTube encoding which is compatible with Flash version 9.0.45 (TODO: Tell them to upgrade!) */
				if (/youtube.com/.test(img.src))
					params = {l: "263"};
					
				params = $.extend(params, $.ewyseGallery.settings.flashparams, {type: img.type, src: img.src});
				
				$(img.obj).appendTo('body').flashembed(params, $.ewyseGallery.settings.flashvars);
				var elem = $('body > div:last');
				img.obj = {html: elem.html()};
				elem.remove();
			}
		}
	},
	
	actions: {
		next: function() {
			$.ewyseGallery._setButton('next',false);
			$.ewyseGallery._setButton('prev',false);
			$.ewyseGallery._setButton('play',false);
			$.ewyseGallery._setCurrentImage('next');
		},
		prev: function() {
			$.ewyseGallery._setButton('next',false);
			$.ewyseGallery._setButton('prev',false);
			$.ewyseGallery._setButton('play',false);
			$.ewyseGallery._setCurrentImage('prev');
		},
		close: function() {
			with ($.ewyseGallery) {
				show = false;
				slideshow.stop();
				window.$(window).add(document).unbind('.gallery');
				$['image'].remove();
				$['info'].slideUp('fast');
				$['close-link'].hide();
				$['gallery'].hide();
				$['overlay'].fadeOut();
				window.$('embed, object, select').css({ 'visibility' : 'visible'});
			}
		},
		info: function() {
			$.ewyseGallery.$['info'].slideToggle();
		},
		play: function() {
			$.ewyseGallery.slideshow.start();
		},
		pause: function() {
			$.ewyseGallery.slideshow.stop();
		},
		overview: function() {
			
		}
	},
	
	slideshow: {
		start: function() {
			$.ewyseGallery.$['b']['play'].hide();
			$.ewyseGallery.$['b']['pause'].show();
			$.ewyseGallery._setButton('next',false);
			$.ewyseGallery._setButton('prev',false);
			this.setTimeout();
		},
		setTimeout: function() {
			clearTimeout(this.timer);
			this.timer = setTimeout(this.next, $.ewyseGallery.settings.slideshowInterval);
		},
		next: function() {
			$.ewyseGallery._setCurrentImage('next');
		},
		toggle: function() {
			if (!this.timer) this.start();
			else this.stop();
		},
		stop: function() {
			clearTimeout(this.timer);
			this.timer = false;
			$.ewyseGallery.$['b']['pause'].hide();
			$.ewyseGallery.$['b']['play'].show();
			$.ewyseGallery._setButton('prev', true);
			$.ewyseGallery._setButton('next', true);
		}
	},
	
	_setCurrentImage: function(which) { // show the loading
		// Show the loading
		if (this.loading) return;
		var img = this._getImage(which, true);
		if (img !== false) {
			this.loading = true;
			this.$['info'].slideUp('fast');
			this._preload(img, this._setImage);
		}
	},
	
	_preload: function(img, callback) {
		// get the image object, if not yet supplied
		if (!((typeof img == 'object') || (img = this._getImage(img)))) return;
		
		if (!img.type) {
			img.type = /youtube.com/.test(img.src.toLowerCase()) ? 'youtube' : img.src.split('.').pop().toLowerCase();
		}
		if (img.type && !img.plugin) img.plugin = this.settings.plugins[img.type];
		
		// Store the context, because THIS won't be available in callback functions
		var context = this;
		var newHeight = Math.max(this.settings.maxHeight, this.settings.minHeight) - this.settings.borderSpace * 2;
		var newWidth = Math.max(this.settings.maxWidth, this.settings.minWidth) - this.settings.borderSpace * 2;
		// If we already have an image object, see if we have a callback
		if (img.obj) {
			if (callback) {
				if (img.plugin) {
					callback.call(context, img.obj);
					context = null;
					return;
				} else if (!this.data.rewriteFunction || img.width == newWidth && img.height == newHeight) {
					// If the image is already loaded we apply the callback already, unless the max image size has changed, so we have to reload the image
					if (img.obj.complete) {
						callback.call(context, img.obj);
						context = null;
						return;
					} else {
						$(img.obj).one('load', function(e) {
							callback.call(context, this);
							context = null;
						});
						this.$['loading'].show();
						return;
					}
				}
			}
		} else {
			img.obj = new Image();
			if (img.plugin) this.plugins[img.plugin].create(img);
		}

		if (callback) {
			this.$['loading'].show();
			if (img.plugin) {
				callback.call(context, img.obj);
				context = null;
			} else 
				$(img.obj).one('load', function(e) {
					callback.call(context, this);
					context = null;
				});
		}
		
		// Store the currently used width and height, so we know when to reload the images
		if (!img.plugin) {
			img.width = newWidth;
			img.height = newHeight;
			img.obj.src = this.data.rewriteFunction ? this.data.rewriteFunction.apply(context, [img.src, img.width, img.height]) : img.src;
		}
		img.obj.title = img.obj.alt = $.trim(img.title) || '';
	},
	
	_setImage: function(img) {
		var newHeight, newWidth, h, w, $img = $(img.html || img), imgHeight = parseInt(img.height || $img.attr('height')), imgWidth = parseInt(img.width || $img.attr('width'));
		if (this.settings.fitImage) {
			newHeight = imgHeight + (this.settings.borderSpace * 2);
			newWidth = imgWidth + (this.settings.borderSpace * 2);
		} else {
			newHeight = Math.max(Math.min(imgHeight + this.settings.borderSpace * 2, this.settings.maxHeight), this.settings.minHeight);
			newWidth = Math.max(Math.min(imgWidth + this.settings.borderSpace * 2, this.settings.maxWidth), this.settings.minWidth);
		}

		/* If the user has no plugin installed, these are set to NaN, so use default */
		if (!parseInt(newHeight))
			newHeight = 200;
		if (!parseInt(newWidth))
			newWidth = 400;
			
		h = this.$['image-container'].height() != newHeight;
		w = this.$['container'].width() != newWidth;

		// Helper function
		function temp() {
			$.ewyseGallery.$['image'] = $img.css('display', 'none').appendTo($.ewyseGallery.$['image-container']);
			$.ewyseGallery._loadingDone(img);
			if (h && $.ewyseGallery.options.showInContent !== true) $.ewyseGallery._setPosition();
		}
		
		this.$['image'].fadeOut(300, function() {
			if ($.ewyseGallery.slideshow.timer) $.ewyseGallery.slideshow.setTimeout();
			else {
				var enabled = $.ewyseGallery.data.images.length > 1;
				$.ewyseGallery._setButton('play', enabled);
				$.ewyseGallery._setButton('prev', enabled);
				$.ewyseGallery._setButton('next', enabled);
			}
			$(this).remove();
			if (h || w) {
				// hide the close link on resize.. otherwise it's cropped
				$.ewyseGallery.$['close-link'].hide();
				$.ewyseGallery.$['image-container'].animate({height: newHeight, width: newWidth}, 400, 'easeInOutCubic');
				$.ewyseGallery.$['container'].animate({width: newWidth}, 400, 'easeInOutCubic', temp);
			} else temp();
		});
	},

	_loadingDone: function(img) {
		this._setButton('info', img.title);
		if (!!$.trim(img.title)) this.$['info'].slideDown().children().html(img.title);
		this.$['close-link'].show();
		this.$['loading'].hide();
		this.$['index'].html((this.current + 1) + ' / ' + this.data.images.length);
		this.$['image'].center().fadeIn(300);
		this.loading = false;
		this._preload('next');
	},

	_getImage: function(which, setCurrent) {
		var index = which;
		if (typeof index == 'undefined') index = this.current;
		else switch(which) {
			case 'current':
			case 'active': index = this.current; break;
			case 'prev': index = this.__testIndex(this.current - 1) !== false ? this.current - 1 : this.data.images.length - 1; break;
			case 'next': index = this.__testIndex(this.current + 1) || 0; break;
			case 'last': index = this.data.images.length - 1; break;
			case 'first': index = 0; break;
		}
		index = this.__testIndex(index);
		if (index !== false && setCurrent) this.current = index;
		return $.ewyseGallery.data.images[index];
	},
	
	__testIndex: function(index) {
		return (index >= 0 && index < this.data.images.length) ? index : false;
	},
	
	_setButton: function(which, enabled) {
		this.$['b'][which].unbind().css({opacity: enabled ? 1 : 0.4, cursor: enabled ? 'pointer' : 'auto'});
		if (enabled) this.$['b'][which].bind('click', this.actions[which]);
	},
	
	_show: function(args, index) {
		this._initInterface();
		var self = this;
		this.show = true;
		// Set the collection we are currently viewing.
		$('embed, object, select').css({ 'visibility' : 'hidden'});

		if (!this._settings) this._settings = $.extend({}, this.settings);
		
		if (this.options.showInContent !== true) {
			this._setPosition();
			this.$['gallery'].css({
				position: 'absolute',
				left: 0,
				top: 0
			});
			var windowTimeout = false;
			$(window).bind('scroll.gallery resize.gallery', function(e) {
				clearTimeout(windowTimeout);
				windowTimeout = setTimeout(function() {self._setPosition(e);}, 50);
			});
		}

		this.data = args;
		
		if (typeof index != 'undefined') this.current = index;
		
		if (this.data.images.length > 1) {
			this._setButton('play', true);
			this._setButton('pause', true);
			this.$['controls'].show();
		} else {
			this.$['controls'].hide();
		}
		
		this.$['gallery'].fadeIn(function () {
			$.ewyseGallery._setCurrentImage();
			if (args.autoplay) $.ewyseGallery.slideshow.start();
		});
		
		this._bindKeys();
	},
	
	_bindKeys: function() {
		var self = this;
		$(document).bind('keydown.gallery', function(e) {
			var prevent = true;
			if (e.which == 32) self.slideshow.toggle();
			else if (e.keyCode == 39) self.actions.next();
			else if (e.keyCode == 37) self.actions.prev();
			else if (e.keyCode == 27 && self.options.showInContent !== true) self.actions.close();
			else prevent = false;
			// If we don't do this, we stop the animated GIF as well
			if (prevent) {
				e.stopPropagation();
				e.preventDefault();
			}
		}).click();
	},
	
	_setPosition: function(y) {
		if (!$.ewyseGallery.show) return;
		var $win = $(window), $doc = $(document), scrollTop = $win.scrollTop(), winHeight = $win.height();
		var location = $win.scrollTop() + (winHeight / 2) - ($.ewyseGallery.$['gallery'].height() / 2);
		if (!isNaN(y)) height = Math.max(y, $doc.height());
		if ($.ewyseGallery.settings.autoMaxDimension) {
			$.ewyseGallery.settings.maxWidth = Math.min($win.width() - 50, $.ewyseGallery._settings.maxWidth);
			$.ewyseGallery.settings.maxHeight = Math.min(winHeight - 150, $.ewyseGallery._settings.maxHeight);
		}
		if (location > scrollTop) {
			$.ewyseGallery.$['gallery'].stop().animate({top: location, opacity: 1}, 'fast', 'easeOutCubic', function() {
				if ($.browser.msie) $.ewyseGallery.$['gallery'].css('filter', '');
				$.ewyseGallery.$['overlay'].css('height', $doc.height()).not(':visible').fadeIn('fast');
			});
		} else {
			$.ewyseGallery.$['overlay'].css('height', $doc.height()).not(':visible').fadeIn('fast');
			if (!y || !isNaN(y)) $.ewyseGallery.$['gallery'].animate({top: scrollTop + 30}, 'fast', 'easeOutCubic');
		}
	}
};

$.fn.center = function() {
	return this.each(function() {
		var $$ = $(this);
		$parent = $$.parent().css({position: 'relative'});
		$$.css({
			position: 'absolute',
			left: ($parent.width() - $$.width()) / 2,
			top: ($parent.height() - $$.height()) / 2
		});
	});
}

$.fn.ewyseGallery = function(arg, arg2) {
	// If $arg is a string, then $this is a grouping and $arg are the items per group.
	if (typeof arg == 'string') {
		// Make a gallery of each of the selected items
		return this.each(function() {
			var $$ = $(this);
			arg2 = arg2 || {};
			// If a thumbnail jquery path is given (as a string), get it for each item
			if (arg2.thumbnails && typeof arg2.thumbnails == 'string') arg2.thumbnails = $$.find(arg2.thumbnails).get();
			$$.find(arg).ewyseGallery($.extend({}, arg2));
		});
	} // Else, $this is the path to all the gallery items
	var options = arg || {};
	options.images = options.images || [];
	options.thumbnails = options.thumbnails ? (typeof options.thumbnails == 'string' ? $(options.thumbnails).get() : options.thumbnails) : [];
	var jsonLoaded = false;
	
	function _initialize(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var obj = this;
		if (!jsonLoaded && options.mode && options.mode.toLowerCase() == 'json') {
			if ($.ewyseGallery.settings.cacheAjaxRequests && (options.images = $.ewyseGallery.settings.cache[options.url])) {
				jsonLoaded = true;
				_start(obj, options);
			} else {
				$.getJSON(options.url, function(json) {
					jsonLoaded = true;
					options.images = json.images || json;
					if (options.jsonCallback) options.images = options.jsonCallback.call(this, options.images);
					// cache the request? Good for catching identical requests
					if ($.ewyseGallery.settings.cacheAjaxRequests) {
						$.ewyseGallery.settings.cache[options.url] = options.images;
					}
					_start(obj, options);
				});
			}
		} else _start(obj, options);
		
		return false;
	};
	
	function _start(obj, data) {
		var img = obj.href || obj.src;
		var index = 0;
		for (i = 0; i < data.images.length; i++) {
			if (data.images[i].src.indexOf(img) > -1) {
				index = i; break;
			}
		};
		$.ewyseGallery._show(data, index);
	}
	
	if (!options.images.length) this.each(function () {
		var img = {
			src: this.href || this.src,
			title: this.title || this.alt
		};
		img.type = ($.metadata ? $(this).metadata().type : false) || $(this).data('type') || false;
		options.images.push(img);
	});
	return this.unbind('.gallery').bind('click.gallery', _initialize);
};

})(jQuery);