// JavaScript Document
;(function ($) {
$.shadow = {
	defaults: {
		imagePath: 'assets/images/shadow/', // The path to the image set
		imageType: 'png', // the extension to use for the images
		corners: 'tl tr bl br', // The corners to generate as elements
		sides: 'top right bottom left', // The sides to generate as elements
		dimensions: false, // Defines the size of the corner images (and thus the side images). If not set, the plugin will calculate the dimensions by loading the first corner image. To not delay the plugin, explicitly set this parameter
		// Below is en example of how to set this variable.
//		{
//			width: 5,
//			height: 5
//		},
		wrap: 'inside', // Whether we should create a wrapper element for the content inside the selected elements
		noPadding: false, // Reset padding of the selected elements to zero?
		margin: false, // defaults to 'sides' defined above.. it will compensate on margins defined here, to try to maintain the layout
		callback: false, // Fires when the corner images are inserted
		fixWidth: false, // Maintain the original width by fixing it
		innerOverlap: false // When the added images overlap with the inner content, instead of just closing the gap
	},
	remove: function() {
		$('.jq_shadow').shadow('remove');
	}
};

$.fn.shadow = $.browser.msie && $.browser.version < 7 ? function() { return this; } : function(options) {
	// Want to remove the shadow?
	if (options && options.constructor == String && options == 'remove') {
		return this.each(function() {
			var $$ = $(this);
			if (!$$.is('.jq_shadow')) return;
			if ($$.is(':has(> .jq_shadow_inner)')) {
				$$.css({border: '', background: '', margin: '', padding: '', width: ''}).children('.jq_shadow_inner').css({padding: 0, width: 'auto'});
			}
			$$.removeClass('jq_shadow').children('.jq_shadow_element').remove();
		});
	}
	
	options = $.extend(true, {}, $.shadow.defaults, options || {});
	if (options.margin === false) options.margin = options.sides;
	var $$ = this;
	
	function num(elem, prop) {
		// Ok, this may look weird, why not use parseInt? Because FF sometimes gives me 0.98333px instead of 1px o.O
		return elem[0] && Math.round(parseFloat( jQuery.curCSS(elem[0], prop, true), 10 )) || 0;
	}
	
	// Returns the corner image URLs when needed, calculated by the path, which to get and the image type.
	function img(which) {
		return options.imagePath + which +  '.' + options.imageType;
	}
	
	// tmp stores the first corner defined in the corners arg. It will be used to calculate the dimensions of the corners
	var tmp = options.corners.split(' ')[0], src;
	if (tmp && (src = img(tmp))) {
		var testImg = new Image();
		$(testImg).one('load', function() {
			options.dimensions = {
				width: this.width,
				height: this.height
			};
			process($$, options);
		});
		testImg.src = src;
	} else {
		setTimeout(function() {
			process($$, options);
		},0);	
	}
	
	function process(elems, opts) {
		elems.each(function() { processElement.call(this, opts); });
	}	
	
	function processElement(opts) {
		var self = $(this), elements, css = {}, src, padding = {};
		if (self.is('.jq_shadow')) return;
		self.addClass('jq_shadow');
		
		if (!opts.noPadding) {
			// first reset padding
			css.padding = '0px';
			if (/t/.test(opts.corners) || /top/.test(opts.sides)) padding.top = opts.dimensions.height;
			if (/r/.test(opts.corners) || /right/.test(opts.sides)) padding.right = opts.dimensions.width;
			if (/b/.test(opts.corners) || /bottom/.test(opts.sides)) padding.bottom = opts.dimensions.height;
			if (/l/.test(opts.corners) || /left/.test(opts.sides)) padding.left = opts.dimensions.width;
			$.each(padding, function(key, value) {
				css['padding-' + key] = value;
				if (opts.margin && opts.margin.indexOf(key) > -1) css['margin-' + key] = -(value - num(self, 'padding-' + key)) + num(self, 'margin-' + key);
			});
		}
		
		if (opts.wrap == 'inside') {
			var cssInner = {};
			if (!self.is(':has(> .jq_shadow_inner)')) {
				var tempVal, bgArray = ['backgroundColor','backgroundScroll','backgroundPosition','backgroundImage','backgroundRepeat'];
				for (var i = 0; i < bgArray.length; i++) {
					if (tempVal = self.css(bgArray[i])) cssInner[bgArray[i]] = tempVal;
				};
				css.background = 'transparent';
				self.wrapInner('<div class="jq_shadow_inner"></div>');
			}
			
			// Inherit the padding and margin settings
			var borderWidth, tempPadding, tempSide;
			$.each('left right top bottom'.split(' '), function() {
				tempSide = this.toString();
				tempPadding = num(self, 'padding-' + tempSide);
				borderWidth = num(self, 'border-' + tempSide + '-width');
				if (opts.innerOverlap) {
					cssInner['margin-' + tempSide] =  tempPadding - (padding[tempSide] || 0) + borderWidth;
					cssInner['padding-' + tempSide] = Math.max(tempPadding, 0);
				} else {
					cssInner['padding-' + tempSide] = Math.max(tempPadding - (padding[tempSide] || 0), 0) + borderWidth;
				}
			});
			// Should we set the width if the inner element? If it is defined and requested, yes please.
			if (opts.fixWidth) {
				var tempWidth = num(self, 'width');
				if (tempWidth > 0) cssInner.width = tempWidth;
			}
			
			// MS IE? Let's zoom to 1!!!11oneone
			if ($.browser.msie) {
				cssInner.zoom = 1;
				css.zoom = 1;
			}
			
			self.children('.jq_shadow_inner').css(cssInner);
			css.border = 'none';
			css.background = 'transparent';
		}
		
		if (self.css('position') != 'absolute') css.position = 'relative';
		
		self.css(css);
		
		// Re-use the CSS object to use for the elements we will add.
		css = {
			position: 'absolute'
		};
		
		if ($.browser.msie) {
			css.lineHeight = 0;
			css.fontSize = 0;
		}
		
		$.each(opts.sides.split(' '), function() {
			var $$ = $('<span>&nbsp;</span>').css(css).addClass(this + ' jq_shadow_element');
			var temp = {
				backgroundImage: 'url(' + img(this) + ')'
			};
			if (/left|right/.test(this)) {
				temp[this] = 0;
				temp.backgroundRepeat = 'repeat-y';
				temp.width = opts.dimensions.width;
				temp.top = opts.corners.indexOf('t' + this.charAt(0)) > -1 ? opts.dimensions.height : 0;
				temp.bottom = opts.corners.indexOf('b' + this.charAt(0)) > -1 ? opts.dimensions.height : 0;
			}
			else if (/top|bottom/.test(this)) {
				temp[this] = 0;
				temp.backgroundRepeat = 'repeat-x';
				temp.height = opts.dimensions.height;
				temp.left = opts.corners.indexOf(this.charAt(0) + 'l') > -1 ? opts.dimensions.width : 0;
				temp.right = opts.corners.indexOf(this.charAt(0) + 'r') > -1 ? opts.dimensions.width : 0;
			}
			$$.appendTo(self).css(temp);
		});
		
		$.extend(css, opts.dimensions);
		css.backgroundRepeat = 'no-repeat';
		$.each(opts.corners.split(' '), function() {
			var $$ = $('<span>&nbsp;</span>').css(css).addClass(this + ' jq_shadow_element');
			var temp = {
				backgroundImage: 'url(' + img(this) + ')'
			};
			if (/b/.test(this)) temp.bottom = 0;
			if (/t/.test(this)) temp.top = 0;
			if (/l/.test(this)) temp.left = 0;
			if (/r/.test(this)) temp.right = 0;
			$$.appendTo(self).css(temp);
		});
		
		if (options.callback) options.callback.apply(self);
	}
	
	return this;
};

})(jQuery);