/*
 *	2008, 25th of August
 *	
 *	Author:		Yereth Jansen (http://www.yereth.nl)
 *	Copyright:	Wharf (http://www.wharf.nl)
 *	Version:	0.91
 *	
 *	Usage:
 *	$('#myMenu a').ajaxNav(options);
 *	
 *	Server side script which delivers content items similar to the 'target' option.
 *
 *	Either the 'target' or the 'workspace' should be explicitly set. If the 'target' is not set
 *	the 'target' will be the direct first child of the 'workspace'. If the 'workspace' is not set,
 *	the parent of the 'target' will be used, unless one enables 'createWorkspace'. It's recommended
 *	to either pre-create a workspace or enable 'createWorkspace', so the direct parent can be used
 *	as a viewport.
 *	
 *	A few dependancies:
 *	$.metadata			Uses the metadata plugin, if available, to retrieve extra parameters on links: <a class="someclass {ajaxParams: {output: 'json'}}"> for instance.
 *	$.fn.hashchange		Used if available to have cross browser history support with hashes.
 *	$.fn.imageReady		Used to wait for images to load in order to calculate the right dimensions of certain elements.
 *	window.pageTracker	If enabled and available, will track pages on show in google analytics
 */

(function($){

$.ajaxNav = {
	version: 0.93,
	
	// The last hash is for the hash poll, so it won't check if the hash is the same as the last time
	lastHash: false,
	
	defaults: {
		ajaxFile:		window.location.protocol + '//' + window.location.host + window.location.pathname,	// The location of the file which handles the ajax requests
		ajaxParams:		{},					// Parameters passed along with the ajax call
		hash:			true,				// Disable the hashing function? Could be wise, with complex sites, as it won't work gracefully together with other ajax content.
		hashStyle:		'path',				// Options: 'text' (will take the text of the link to create the hash) or 'path', which will use the url relative path
		hashPath:		'',					// Used to remove a part of the path when generating hashes
		preload:		true,				// Preload content?
		target:			false,				// The current content item, which will be the base for the dimensions of all other items
		dimensional:	true,				// Should the content move around in 1 or 2 dimensions?
		workspace:		false,				// The workspace, if already available, wherein the content items will be layed out
		createWorkspace:false,				// Create the workspace wherein content is layed out
		createViewport:	false,				// Create the viewport wherein the workspace exists
		resizeViewport: false,				// only works when dimensional is selected and no submenu is used.. use this when the content
											// should not all have the same height 
		fixViewport:	false,				// If true, the viewport will get fixed dimensions, as it is when we find it
		dynamicWidth:	false,				// When fixing the viewport, we can resize the width when the window width is resized
		dynamicWidthCallBack: false,		// callback after the dynamic width is set
		onShow:			function(target, previousTarget) {},		// Function called whenever content is shown (element dimensions will be available) (called every time the target content is shown)
		onBefore:		function(target, previousTarget) {},		// Function called before targeted content is shown (element dimensions will be available) (called every time the target content is shown)
		callback:		false,				// Function called when content is added (content might be hidden) (so onload) (called only once; on content load (with AJAX)
		beforeLoad:		false,
		onLoad:			false,
		subMenu:		false,				// Query which points to the submenu in the content items, which will be used for vertical navigation
		classActive:	'activeItem',		// The classname marking the currently active navigation item
		processBackLinks:true,				// Find links in the page that point to the same item
		trackGA:		false,				// Track navigation with google analytics, when available (works with ua, not with urchin)
		useOverlay:		false,				// Use a clickable overlay on inactive content
		keyNav:			false,				// Use the arrow keys to navigate through this navigation
		windowResize:	false,				// Center the content on window resize event
		fade:			true,				// Fade the incoming menu? Might be heavy on the cpu and doesn't look nice in IE with transparent PNGs
		find:			false,				// In the AJAX result, find a specific element to use (jquery style), instead of the complete content result
		fx:				{					// Default methods used for effects
							showViewport: {opacity: 1},
							hideViewport: {opacity: 0}
						},
		offset:			[50, 50],			// Distance between content items [x, y]
		buttons:		false,				// Add buttons for navigation. Usage: {left: image, right: image, down: image, up: image, index: /horizontal|vertical|both/}
		dragDrop:		false,				// BETA!!!!!!!
		easing:			'easeInOutCubic'	// Default easing option
	},
	
	parseGetParameters: function(url) {
		var array = {}, index, args, arg, i, key;
		if ((url || (!noEnv && (url = window.location.search))) && (index = url.indexOf('?')) !== -1) {
			args = url.substring(index + 1).split(/&amp;|&/);
			for (i = 0; i < args.length; i++) {
				arg = args[i].split('=');
				// Remove the first entry; it's our key in this pair
				key = arg.shift();
				// If the value also contains unencoded '=' signs, we save the value by rejoining the remainings
				arg = arg.join('=');
				array[key] = (typeof(arg) != 'undefined') ? decodeURIComponent(arg) : '';
			}
		}
		return array;
	},
	
	// Storage for all the instances of ajax navigation
	cache: [],
	
	init: function(settings) {
		var o = settings.options;
		if (settings.mainNav.is('.ajaxNav')) return;
		
		// If we have a workspace set but the target is empty, we want to auto load the target first, based on the active item
		if ((o.workspace || o.workspace.length) && (!o.target || !o.target.length)) {
			settings.mainNav.filter('.' + o.classActive).each(function() {
				var url = this.href, params = $.extend($.ajaxNav.parseGetParameters(url), settings.options.ajaxParams);
				var base = url.split('?').shift();
				$.getJSON(encodeURI(base), params, function(json) {
					content = $(json.content);
					if (settings.options.find) content = content.find(settings.options.find);
					o.target = content.hide().appendTo($(o.workspace));
					if (o.callback) callback.apply(o.target);
					o.target.fadeIn(function() {
						if (o.onShow) o.onShow.apply(o.target, [o.target]);
						$.ajaxNav.init(settings);
					});
				});
			});
			return;
		}
		
		// Add to our list of cache objects
		this.cache.push(settings);
		
		// Set the root (for deciding how hashes should look, for instance
		settings.root = o.root || window.location.protocol + '//' + window.location.hostname + window.location.pathname;
		// Create the back link log, to process links in the page which refer to ajaxNav processed links..
		settings.backLinkLog = {};
		// Create the backlog for hashes, so we can map the current hash to content items
		settings.backHashLog = {};
		// Assume the rootURL (no window.location.search) refers to the [0,0] item..
		if (settings.options.keyNav) settings.backLinkLog[settings.root] = [0,0];
		
		// No workspace and no target? Zaijian! We can't work without
		if ((!o.workspace || !o.workspace.length) && (!o.target || !o.target.length)) return;
		
		// See if we have an active main nav item
		settings.mainNav.each(function(i) {
			if ($(this).is('.' + o.classActive)) settings.activeItem = [i,0];
		});
		
		// Do we have an active submenu item?
		if (o.subMenu) $(o.subMenu, target).each(function(i) {
			if ($(this).is('.' + o.classActive)) settings.activeItem[1] = i;
		});
		
		// Create our main navigation object
		this.createNav(settings, settings, settings.mainNav);
		
		// Exception for non-navigation items... if no active item is detected, the activeItem is [-1,-1]
		if (settings.activeItem[0] == -1) {
			settings.nav[-1] = {nav: []};
			settings.nav[-1].nav[settings.activeItem[1]] = {};
		}
		
		if (o.subMenu) this.createSubMenu(settings, settings.activeItem, true);
		settings.currentTarget = settings.activeItem;
	
		// Get the current content target, the workspace and the viewport
		var target = this.getNav(settings, settings.activeItem)['target'] = o.target ? $(o.target) : $(':first-child', o.workspace);
		if (!target.length) return;
		settings.workspace = o.workspace ? $(o.workspace) : o.createWorkspace ? target.wrap("<div/>").parent() : $(target).parent();
		settings.viewport = o.createViewport ? settings.workspace.wrap("<div/>").parent() : settings.workspace.parent();
		settings.workspace.addClass('ajaxNav_workspace');
		settings.viewport.addClass('ajaxNav_viewport');
		// Rediculous IE fix to make sure the viewport is rendered correctly and returns the right height
		if (navigator.appName.indexOf("Internet Explorer")!=-1) settings.viewport.add(settings.workspace).css('zoom', 1);
		
		// Bindings to show and hide the viewport
		settings.mainNav.bind('showViewport', function(a, callback) {
			settings.viewport.stop().css('display', 'block').animate(o.fx.showViewport, (function() {
				$.ajaxNav.dynamicWidth(false, settings);
				settings.hidden = false;
				if (callback && $.isFunction(callback)) { callback.call(); }
			}));
		}).bind('hideViewport', function(a, b, c) {
			settings.hidden = true;
			settings.viewport.stop().animate(o.fx.hideViewport, function() { settings.viewport.css('display', 'none'); });
		});

		settings.contentWidth = target.width();

		// Create the nav buttons before processing backlinks!
		if (o.buttons) {
			this.initButtons(settings);
		}
		
		settings.viewportWidth = settings.viewport.width();
		
		if (o.dimensional) {
			// Set some dimension and location values
			settings.contentHeight = target.height() || target.append('<div style="clear: both" />').height();
			settings.contentOuterWidth = target.outerWidth();
			settings.contentOuterHeight = target.outerHeight();
			settings.windowWidth = $(window).width();
			settings.left = settings.viewport.width() / 2 - 5000;
			settings.top = 0;

			if (o.fixViewport) {
				settings.viewport.css({
					overflow: 'hidden',
					height: settings.viewport.height(),
					position: settings.viewport.css('position') == 'absolute' ? 'absolute' : 'relative'
				});
				if (!o.dynamicWidth) settings.viewport.css('width', settings.viewportWidth);
			}

			settings.workspace.css({
				width: 10000,
				height: 10000,
				position: 'absolute',
				left: settings.left,
				top: settings.top,
				textAlign: 'center'
			});

			target.css({
				cursor: 'auto',
				textAlign: 'left',
				position: 'absolute',
				top: target.position().top,
				left: 5000 - settings.contentOuterWidth / 2,
				width: settings.contentWidth
			});
			if (!o.resizeViewport) target.css('height', settings.contentHeight);
		} else {
			settings.workspace.css({position: settings.workspace.css('position') == 'absolute' ? 'absolute' : 'relative'});
			target.css({width: settings.contentWidth});
		}
		
		if (o.dragDrop) settings.workspace.css({
			cursor: 'move'
		}).bind('mousedown.ajaxNav', function(e) {
			$.ajaxNav.drag.start(e, settings);
		});
		
		var dynamicWidthTimeout;
		if (o.dynamicWidth) $(window).bind('resize.ajaxNav', function(e) { 
			clearTimeout(dynamicWidthTimeout);
			setTimeout(function() {
				$.ajaxNav.dynamicWidth(e, settings);
			}, 100);
		});
		$.ajaxNav.dynamicWidth(false, settings);
		if (o.windowResize) $(window).bind('resize.ajaxNav', $.ajaxNav.setPosition);
		if (o.useOverlay && !this.compare(settings.activeItem,[-1,-1])) this.addOverlay(settings, settings.activeItem);
		if (o.keyNav) this.initKeyNav(settings);
		
		if (settings.options.preload) setTimeout(function() { $.ajaxNav.preloadNeighbors(settings); }, 500);
		
		if (o.hash) {
			// Set the current hash
			this.setHash(settings, settings.activeItem, true);
			if (!this.hashEnabled) {
				this.hashEnabled = true;
				// Use the hashchange plugin if available, as it supports cross browser history and utilises the hashevent, when available; far more efficient than polling!
				if ($.fn.hashchange) $(window).hashchange($.ajaxNav.hashchange);
				else setInterval($.ajaxNav.hashchange, 100);
			}
		}
		
		// Enable buttons
		if (o.buttons) this.setButtons(settings);
		
		// See if we find links in the page that refer to the same links we processed here
		this.processBackLinks(settings);
		
		return settings;
	},
	
	initButtons: function(settings) {
		settings.buttons = {};
		var action;
		for (action in settings.options.buttons) {
			if (this.nav[action]) {
				var button = settings.options.buttons[action];
				settings.buttons[action] = (button.constructor == String) 
					? $('<img/>').css('cursor', 'pointer')
						.attr({
						  src: settings.options.buttons[action],
						  alt: action
						}).addClass(action)
						.appendTo(settings.viewport.parent())
					: button;
				settings.buttons[action].addClass('ajaxNav').bind('click.ajaxNav', function(e) {
					e.preventDefault();
					this.blur();
					$.ajaxNav.nav[$(this).data('action')](settings);
				});
				settings.buttons[action].data('action', action);
			}
		}
	},
	
	initKeyNav: function(settings) {
		$(document).bind('keydown.ajaxNav', function(e) {
			if (settings.hidden) return true;
			if ($(e.target).is(':input')) return true;
			var action = false, cancel = false;
			if (37 == e.keyCode) action = 'left';
			else if (39 == e.keyCode) action = 'right';
			else if (38 == e.keyCode) action = 'up';
			else if (40 == e.keyCode) action = 'down';
			if (action) cancel = $.ajaxNav.nav[action](settings);
			if (cancel) {
				e.preventDefault();
				e.stopPropagation();
			}
			return !cancel;
		});
	},
	
	setButtons: function(settings) {
		var b = settings.buttons;
		if (!b) return;
		if (b.left) b.left[this.getNav(settings, settings.activeItem[0] - 1) ? 'show' : 'hide']();
		if (b.right) b.right[this.getNav(settings, settings.activeItem[0] + 1) ? 'show' : 'hide']();
		if (b.up) b.up[this.getNav(settings, [settings.activeItem[0], settings.activeItem[1] - 1]) ? 'show' : 'hide']();
		if (b.down) b.down[this.getNav(settings, [settings.activeItem[0], settings.activeItem[1] + 1]) ? 'show' : 'hide']();
	},
	
	nav: {
		left: function(settings) {
			return $.ajaxNav.navigate(settings, [settings.activeItem[0] - 1, settings.activeItem[1]]);
		},
		right: function(settings) {
			return $.ajaxNav.navigate(settings, [settings.activeItem[0] + 1, settings.activeItem[1]]);
		},
		up: function(settings) {
			return $.ajaxNav.navigate(settings, [settings.activeItem[0], settings.activeItem[1] - 1]);
		},
		down: function(settings) {
			return $.ajaxNav.navigate(settings, [settings.activeItem[0], settings.activeItem[1] + 1]);
		}
	},

	drag: {
		start: function(e, settings) {
			if (e.target != settings.workspace[0]) return true;
			settings.drag = {
				x: -e.clientX + settings.left,
				y: -e.clientY + settings.top + settings.topOffset,
				startX: e.clientX,
				startY: e.clientY
			};
			$(window).bind('mousemove.ajaxNav', function(e) {
				$.ajaxNav.drag.drag(e, settings);
			});
			$(window).one('mouseup.ajaxNav', function(e) {
				$.ajaxNav.drag.stop(e, settings);
			});
		},
		drag: function(e, settings) {
			settings.workspace[0].style.left = (settings.drag.x + e.clientX) + 'px';
			settings.workspace[0].style.top = (settings.drag.y + e.clientY) + 'px';
			return false;
		},
		stop: function (e, settings) {
			$(window).unbind('mousemove.ajaxNav');
			$.ajaxNav.setPosition(settings);
		}
	},
	
	createSubMenu: function(settings, which) {
		var nav = this.getNav(settings, which[0]);
		var subMenu = $(settings.options.subMenu, this.getNav(settings, which, 'target'));
		
		this.createNav(settings, nav, subMenu, which[0]);
		this.processBackLinks(settings);
	},

	createNav: function(settings, addTo, urls, primary) {
		if (!addTo.nav) addTo.nav = [];
		urls.each(function(i) {
			var self = $(this), which, hash;
			if (self.is('.ajaxNav')) return;
			which = typeof primary !== 'undefined' ? [primary, i] : [i, 0];
			if (!addTo.nav[i]) {
				// Create new nav item
				
				hash = '#';
				if (settings.options.hashStyle == 'text') hash = hash + self.text();
				else {
					hash = hash + (settings.options.hashPath ? this.pathname.replace(settings.options.hashPath, '') : this.pathname);
					hash = hash + '&' + decodeURIComponent(this.search.substr(1));
				}
				
				addTo.nav[i] = {
					url: this.href,
					el: this,
					backLinks: [this],
					target: false,
					ajaxParams: $.metadata ? (self.metadata().ajaxParams || {}) : {},
					hash: hash
				};
				
				// Create backlogs to track back items
				settings.backHashLog[addTo.nav[i].hash] = which;
				settings.backLinkLog[addTo.nav[i].url] = which;
			}
			$.ajaxNav.processLink(settings, this, which);
		});
	},
	
	processLink: function(settings, el, which) {
		$(el).not('.ajaxNav').addClass('ajaxNav').bind('click.ajaxNav', function(e) {
			if (e.ctrlKey || e.metaKey) return true;
			e.preventDefault();
			$.ajaxNav.navigate(settings, which);
		});
	},
	
	processBackLinks: function(settings, context) {
		if (!settings) for (i = 0; i < $.ajaxNav.cache.length; i++) this.processBackLinks($.ajaxNav.cache[i], context);
		else {
			// Don't process backlinks if it's set off
			if (!settings.options.processBackLinks) return;
			var which;
			setTimeout(function() {
				$('a', context || document).each(function() {
					// Only handle non processed links
					if (!/ajaxNav/.test(this.className)) {
						if (which = settings.backLinkLog[this.href]) {
							// Add the currently found element to the backlinks so we can add an active class to it when activated
							$.ajaxNav.getNav(settings, which).backLinks.push(this);
							// Process the current link to also work with ajax, instead of its normal way
							$.ajaxNav.processLink(settings, this, which);
						}
					}
				});
			}, 0);
		}
	},
	
	setHash: function(settings, which, ifNotSet) {
		if (settings.options.hash) {
			var hash = this.getNav(settings, which, 'hash') || '#';
			
			if (window.location.hash && ifNotSet) return;
			
			if ($.locationHash) $.locationHash(hash);
			else window.location.hash = hash;
		}
	},
	
	hashchange: function(a) {
		var i, hash = window.location.hash ? decodeURIComponent(window.location.hash) : false;
		if (hash && hash != $.ajaxNav.lastHash) {
			for (i = 0; i < $.ajaxNav.cache.length; i++) {
				if ($.ajaxNav.cache[i].hidden) return;
				// check which element should be selected depending on the current hash
				var which = $.ajaxNav.cache[i].backHashLog[hash] || false;
				if (which && !$.ajaxNav.compare($.ajaxNav.cache[i].activeItem, which) && !$.ajaxNav.compare($.ajaxNav.cache[i].currentTarget, which)) {
					$.ajaxNav.navigate($.ajaxNav.cache[i], which);
				}
			}
		}
		$.ajaxNav.lastHash = hash;
	},
	
	// Compare whether 2 items are the same (in the form: [x, y])
	compare: function(which1, which2) {
		return (which1[0] == which2[0]) && ((which1[1] || 0) == (which2[1] || 0));
	},
	
	// See if a corresponding navigation item exists
	getNav: function(settings, which, property) {
		var result = false;
		// If only given a number, we're looking for a primary nav. item.
		if (which.constructor == Number) {
			result = settings.nav[which];
		// Otherwise, we wish to get a submenu nav. item.
		} else if (which.constructor == Array) {
			// If the submenu item is set, see if it exists.
			if (which[1] != 0) result = settings.nav[which[0]] && settings.nav[which[0]].nav ? settings.nav[which[0]].nav[which[1]] : (settings.options.subMenu ? false : undefined);
			// If not, just return a primary nav. item.s
			else result = settings.nav[which[0]];
		}
		// If a property was set, return that property of the result.
		if (result && property) return result[property];
		else return result;
	},
	
	dynamicWidth: function(e, settings) {
		if (settings.hidden) return;
		if (settings.options.minWidth) {
			settings.viewport.width('auto');
			if (parseInt(settings.viewport.width()) < settings.options.minWidth) {
				settings.viewport.width(settings.options.minWidth);
				if (settings.options.minWidth == settings.viewportWidth) return;
			}
		}
		var newWidth = settings.viewport.width(),
			dif = newWidth - settings.viewportWidth,
			active = this.getNav(settings, settings.activeItem, 'target'),
			left = parseInt(active.css('left'));
		settings.viewportWidth = newWidth;
		settings.contentWidth += dif;
		settings.contentOuterWidth += dif;
		$(settings.nav).each(function(index) {
			if (this.target) {
				$(this.target).css({
					width: settings.contentOuterWidth,
					left: left + (index - settings.activeItem[0]) * (settings.contentOuterWidth + settings.options.offset[0])
				});
			}
		});
		$.ajaxNav.resizeViewport(settings);
		if (settings.options.dynamicWidthCallback) settings.options.dynamicWidthCallback.call(active);
	},
	
	resizeViewport: function(settings, target) {
		target = target || $.ajaxNav.getNav(settings, settings.activeItem, 'target');
		settings.viewport.stop().animate({height: target.outerHeight()}, 'normal', 'easeOutCubic', function() {
			settings.viewport.css('height', 'auto');
		});
	},
	
	setPosition: function(e, animationTime) {
		var width = $(window).width();
		
		// Set the position of the workspace in this function
		function setPos() {
			this.topOffset = (this.options.useOverlay && this.activeItem[1] != 0) ? (this.viewport.height() - this.contentHeight) / 2 : 0;
			if (this.options.windowResize) this.left = this.left - (this.windowWidth - width) / 2;
			this.windowWidth = width;
			this.workspace.stop().animate({left: this.left, top: this.top + this.topOffset}, animationTime || 200, this.options.easing);
		}
		
		// if var e is a settings object of ajaxNav it has a mainNav property, then we just process this one
		if (e && e.mainNav) setPos.apply(e);
		else 
			// process all the items in our global cache, if none is set specifically
			$.each($.ajaxNav.cache, function() {
				if (this.hidden) return;
 				if (!e || this.options.windowResize) setPos.apply(this);
			});
	},
	
	preloadNeighbors: function(settings) {
		var a = settings.activeItem;
		
		if (a.constructor == Array) {
			this.load(settings, [a[0] - 1, a[1]]);
			this.load(settings, [a[0] + 1, a[1]]);
			this.load(settings, [a[0], a[1] - 1]);
			this.load(settings, [a[0], a[1] + 1]);
		} else {
			this.load(settings, [a - 1, 0]);
			this.load(settings, [a + 1, 0]);
		}
	},
	
	load: function(settings, which) {
		var url, nav;
		if ((nav = this.getNav(settings, which)) && (url = nav.url)) {
			if (!nav.target && !nav.loading) {
				if (settings.options.beforeLoad && this.compare(settings.currentTarget, which)) try { settings.options.beforeLoad(); } catch(e) {};
				nav.loading = true;
				var params = $.extend(this.parseGetParameters(url), settings.options.ajaxParams, nav.ajaxParams);
				var base = url.split('?').shift();
				// Removed the encodeURI around base; hopefully this won't give backwards compatibility problems with badly encoded URLs.
				$.getJSON(base, params, function(json) {
					nav.ready = true;
					nav.title = json.title || json.name || '';
					$.ajaxNav.addContent(settings, which, json);
					if (settings.options.onLoad) try { settings.options.onLoad(); } catch(e) {}
					// Use set timeout, so we don't get stack errors
					setTimeout(function() { $(nav.el).trigger('ajaxload'); }, 0);
				});
			// Use set timeout, so we don't get stack errors
			} else if (nav.ready) setTimeout(function() { $(nav.el).trigger('ajaxload'); }, 0);
		}
	},
	
	addContent: function(settings, which, json) {
		var el, active, left, top, target, css, content;
		target = this.getNav(settings, which[0]);
		content = $(json.content);
		if (settings.options.find) content = content.find(settings.options.find);
		if (settings.options.dimensional) {
			active = this.getNav(settings, settings.activeItem, 'target');
			css = {
				height: settings.options.resizeViewport ? 'auto' : settings.contentHeight,
				display: settings.options.fade ? 'none' : 'block',
				left: parseInt(active.css('left')) + (which[0] - settings.activeItem[0]) * (settings.contentOuterWidth + settings.options.offset[0]),
				top: parseInt(active.css('top')) + ((which[1] || 0) - (settings.activeItem[1] || 0)) * (settings.contentHeight + settings.options.offset[1])
			};
		} else {
			css = {
				visibility: 'hidden',
				top: 0,
				left: 0,
				opacity: 0
			};
		}
		
		el = (content).css($.extend(css,
			{
				width: settings.contentWidth,
				position: 'absolute',
				textAlign: 'left',
				cursor: 'auto'
			}))
			.appendTo(settings.workspace);
		if (which[1] > 0) {
			target.nav[which[1]]['target'] = el;
		} else {
			target['target'] = el;
		}
		
		if (settings.options.useOverlay && settings.options.dimensional) {
			$.ajaxNav.addOverlay(settings, which);
			el.fadeIn(500);
		}
		if (settings.options.subMenu) this.createSubMenu(settings, which);
		if (settings.options.callback) settings.options.callback.apply(el);
	},
	
	addOverlay: function(settings, which) {
		var target = this.getNav(settings, which);
		var overlay = $('<a href="' + target.url + '" class="contentOverlay" style="background-color: black; cursor: pointer; position: absolute; z-index: 10;"/>');
		
		overlay.css('opacity', 0.4);
		
		var el = target.target;
		overlay.prependTo(el)
			.css({width: el.width(), height: el.height()})
			.hover(
				function() {
					$(this).stop().fadeTo(200, 0);
				},
				function() {
					$(this).stop().fadeTo(200, 0.4);
				}
			);
		this.processLink(settings, el, which);
		if (this.compare(which, settings.activeItem)) overlay.hide();
	},
	
	navigate: function(settings, which, force) {
		if (!force && this.compare(settings.currentTarget, which)) return false;
		settings.currentTarget = which;
		
		var current = this.getNav(settings, settings.activeItem, 'target');
		var target = this.getNav(settings, which);
		
		// When the target is undefined (instead of false), the user tried to navigate to an illegal target
		if (typeof target == 'undefined') return false;
		else this.setHash(settings, which);
		
		// If we our target is not available yet AND we are in a secondary Navigation level,
		// obviously we first need to get the primary content item, before we can descend into
		// the sub-navigation content item.
		if (!target) {
			if (which[1] > 0) {
				$(this.getNav(settings, which[0]).el).unbind('ajaxload').one('ajaxload', function() {
					$.ajaxNav.navigate(settings, which, true);
				});
				this.load(settings, [which[0], 0]);
			}
			return true;
		}
		var navTo = target.target || false;
		if (!navTo) {
			$(target.el).unbind('ajaxload').one('ajaxload', function() {
				$.ajaxNav.navigate(settings, which, true);
			});
			this.load(settings, which);
			return true;
		}
		
		if ($.isFunction(settings.options.beforeShow)) settings.options.beforeShow.apply(navTo, [navTo, current]);
		
		function onShow() {
			if ($.isFunction(settings.options.onShow)) settings.options.onShow.apply(navTo, [navTo, current]);
			navTo.removeClass('ajaxnav_hidden').addClass('ajaxnav_active');
			current.addClass('ajaxnav_hidden').removeClass('ajaxnav_active');
		}
		
		if (settings.options.dimensional) {
			navTo.show();
			var leftDiff = parseInt(current.css('left')) - parseInt(navTo.css('left'));
			settings.left += leftDiff;
			
			var topDiff = parseInt(current.css('top')) - parseInt(navTo.css('top'));
			settings.top += topDiff;
			
			navTo.hide();
			
			var time = topDiff && leftDiff ? 600 : 450;
			
			if (settings.options.resizeViewport) {
				if ($.fn.imageReady) $('img', navTo).imageReady(function() { $.ajaxNav.resizeViewport(settings, navTo); });
				else $.ajaxNav.resizeViewport(settings, navTo);
			}
			
			if (settings.options.useOverlay) {
				current.children('.contentOverlay').show();
				navTo.children('.contentOverlay').hide();
				if (settings.options.dimensional && (topDiff || leftDiff)) $.ajaxNav.setPosition(settings, time);
				onShow();
			} else if (settings.options.fade) {
				current.stop(false, true).fadeOut(time);
				if (settings.options.dimensional && (topDiff || leftDiff)) $.ajaxNav.setPosition(settings, time);
				navTo.stop(false, true).fadeIn(time, onShow);
			} else {
				if (topDiff || leftDiff) $.ajaxNav.setPosition(settings, time);
				onShow();
			}
		} else {
			settings.workspace.css('height', current.outerHeight());
			function resizeViewport() {
				settings.workspace.stop().animate({height: navTo.outerHeight()}, 'normal', 'easeOutCubic', function () {
					settings.workspace.css('height', 'auto');
				});
			}
			
			function goRightAhead() {
				// Don't go ahead with activating this element when in the meantime another element has been selected
				if (!$.ajaxNav.compare(which, settings.currentTarget)) return; 
				current.css({position: 'absolute', visibility: 'hidden', left: -1000000, opacity: 0});
				navTo.css({position: 'relative', visibility: 'visible', left: 0});
				navTo.stop(true, true).fadeTo('fast', 1, function() {
					// necessary for strange rendering if opacity is not removed
					if (navigator.appName.indexOf("Internet Explorer")!=-1) $(this).css('filter', '');
					onShow();
					resizeViewport();
				});
			}
			
			// If the plugin imageReady is available, use it to wait for images to be loaded. Otherwise we may calculate the height incorrectly, which
			// will of course cause a glitch in the matrix
			if ($.fn.imageReady) $('img', navTo).imageReady(goRightAhead);
			else goRightAhead();
		}

		// Add active class to element we navigate to and remove from the old active link
		$(this.getNav(settings, settings.activeItem[0]).backLinks).removeClass(settings.options.classActive);
		$(this.getNav(settings, which[0]).backLinks).addClass(settings.options.classActive);

		// Now we have a new active item. Set it.
		settings.activeItem = which;

		// Set the buttons right (active or not?)
		this.setButtons(settings);

		// See if you have calculated new ajaxNav links which are also referred to in the content page we navigate to.
		this.processBackLinks(settings, navTo);

		// Track Google Analytics
		if (settings.options.trackGA && target.title) {
			try {
				if (window.pageTracker) pageTracker._trackPageview(target.title);
				else if (window._gaq) _gaq.push(['_trackPageview', target.title]);
			} catch (e) { if (window.console) console.log("Tracking error in ajaxNav: " + e); }
		}
		
		// after a delay, load the neighbors. Give the workspace some time to fluently move towards the active element
		if (settings.options.preload) setTimeout(function() { $.ajaxNav.preloadNeighbors(settings); }, 500);
		return true;
	}
};

$.fn.ajaxNav = function(options) {
	if (!this.length) {
		if (window.console && $.isFunction(window.console.log)) console.log('No items found in ajaxNav call with jQuery selector: "' + this.selector + '"');
		return this;
	}
	
	var settings = {
		mainNav: this,
		activeItem: [-1,-1],
		options: $.extend({}, $.ajaxNav.defaults, options)
	};
	
	return $.ajaxNav.init(settings);
};

})(jQuery);