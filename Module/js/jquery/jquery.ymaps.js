(function($, undefined){

log = function(data) {
	if (window.console) console.log.apply(console.log, log.arguments);
};

var y = $.yMaps = {
	load: function(options, context, callback) {
		// Here we load google maps. Currently only dynamic loading through the google loader is supported.
		if (window.google) {
			// If maps is already loaded, we can go straight to the callback 
			if (google.maps) callback.apply(context);
			// otherwise, we will load the maps through the google loader
			else if (google.load) {
				if (options.region) options.args.region = options.region;
				options.args.language = options.language;
				google.load('maps', '3', {
					language: options.language,
					other_params: $.param(options.args),
					callback: function() {
						y.setMapDefaults();
						callback.apply(context);
					}
				});
			}
		} 
	},
	keycodes: {
		KEY_BACKSPACE	: 8,
		KEY_DELETE		: 46,
		KEY_ENTER		: 13,
		KEY_ESC			: 27,
		KEY_SPACE		: 32,
		KEY_LEFT		: 37,
		KEY_UP			: 38,
		KEY_RIGHT		: 39,
		KEY_DOWN		: 40,
		KEY_COMMA		: 188
	},
	// Defaults for the map, which will be set with the function below
	mapDefaults: false,
	// this function sets the defaults for our maps. We need to do this in a function, as a lot of
	// parameters are not yet available before the map is loaded.
	setMapDefaults: function() {
		y.geocoder = new google.maps.Geocoder();
		y.mapDefaults = {
			 mapTypeControl: false,
			 streetViewControl: false,
			 zoom: 3,
			 center: new google.maps.LatLng(35.86166, 104.195397), // China
			 mapTypeId: google.maps.MapTypeId.ROADMAP,
			 navigationControlOptions: google.maps.NavigationControlStyle.SMALL
		};
	},
	// Stores the geocoder we use for our requests
	geocoder: false,
	// Our defaults for any map
	defaults: {
		language: 'en',
		addressOpacity: 1,
		fx: {
			show: ['slideDown', 500, 'swing']
		},
		args: {
			sensor: false
		}
	},
	setDefaults: function() {
		
	},
	// Zoom levels for different search return types
	zoom: {
		country: 3,
		administrative_area_level_1: 8,
		locality: 10,
		sublocality: 13,
		route: 14,
		street_number: 16,
		street_address: 16
	},
	// Init a map
	init: function(settings) {
		function init(results) {
			// Initialise the map..
			settings.map = new google.maps.Map(settings.div.get(0), settings.o.map);
			// If we have set bounds, load them
			if (settings.o.bounds) y.actions.setSearchBounds(settings, settings.o.bounds);
			// Create the info div, for showing the address
			
			// Wait till the tiles are loaded before showing the map. We want things to be smooth ;)
			google.maps.event.addListenerOnce(settings.map, 'tilesloaded', function() {
				if (settings.o.fx.show)  {
					// Show the map now in the way chosen way
					var fx = settings.o.fx.show, callback;
					// If the last parameter of fx is a function, we need to use it as a callback
					if ($.isFunction(fx[fx.length - 1])) callback = fx.pop();
					// Add callback to the animation
					fx.push(function() {
						// Set the div width back to auto, in cause we want to resize
						settings.div.width('auto');
						google.maps.event.trigger(settings.map, 'resize');
						if (settings.o.callback) settings.o.callback.call(settings.wrapper, settings);
					});
					// Here we show the map with animation of choice
					settings.wrapper
						.css({visibility: 'visible', display: 'none', position: 'relative'})
						[fx[0]].apply(settings.wrapper, fx.slice(1));
				}
				if (settings.o.callback) settings.o.callback.call(settings.div, settings);
			});
			if (settings.o.locationMarker) y.createMarker(settings, results[0]);
			y.actions.setCenter(settings, results);
		}
		
		// If there's no controls, or the controls don't have a result, search for the center point by
		// either a default address or location
		function alt() {
			var search = settings.o.address ? {address: settings.o.address} : {location: settings.o.center};
			settings.o.center = false;
			y.search(search, init, settings.o.language, settings.o.region);
		}
		
		// Load the controls if these are set
		if (settings.o.controls) {
			y.actions.setControls(settings, settings.o.controls);
			// Load the address through the controls 
			y.searchByControls(settings, function(results) {
				if (results) {
					init(results);
				} else alt();
			});
		} else alt();
	},
	setControlsByResult: function(settings, result, notSetMap, noLanguageSearch) {
		var failed = false, zoom, languages = [];
		// Loop through the different controls to see if we have new values for them
		$.each(settings.controls, function(i, control) {
			var lang = control.lang || settings.o.language;
			// Check whether the language corresponds to the result
			if (lang != result.lang) {
				if (!noLanguageSearch && control.lang && ($.inArray(control.lang, languages) === -1)) languages.push(control.lang);
				return;
			}
			var el = control.control;
			// If we failed to set a control before, empty the value and do nothing
			if (failed) {
				el.val('');
				return;
			}
			// The found values per control are added in an array
			var value = [];
			// Loop through the different address components found in the search result
			// so we can match them against what the control supports
			$.each(result.address_components, function(j, comp) {
				// Loop through the different types the control supports, to match them against the
				// different address components in the result
				$.each(control.types, function(i, type) {
					// // If there's a type match, add the value to our list
					if ($.inArray(type, comp.types) != -1) {
						// Set the zoom level if the found type zooms us in more
						if (y.zoom[type] && !zoom || y.zoom[type] > zoom) {
							zoom = y.zoom[type];
						}
						value.push(comp.long_name);
					}
				});
			});
			// If there's a value found, proceed to set the control
			if (value.length) {
				// Join the results, in case there's more than one.
				value = value.reverse().join(' ');
				// Select boxes are set depending on the text value of the options
				if (el.is('select')) {
					// do nothing if the input is not active
					if (!el.is(':disabled,:hidden')) {
						var found = el.children(':contains(' + value + ')');
						// If we found an option with corresponding text, activate it
						if (found.length) {
							if (el.is('[readonly]')) { // If it's readonly just check if the value is the same as we're looking for
								if (el.val() != found.attr('value')) failed = true;
							// Set the value and trigger change, in case plugins monitor the element
							} else el.val(found.attr('value')).trigger('change', ['yMaps']);
						} else failed = true;
					}
				}
				else if (el.is('input')) {
					if (el.is(':disabled,:hidden')); // do nothing if the input is not active
					else if (el.is('[readonly]')) { // Read only fields should match, or we fail 
						if (el.val() != value) failed = true;
					// Set the value in all other cases and trigger blur in case plugins monitor the element
					} else el.val(value).trigger('blur', ['yMaps']);
				}
			} else failed = true;
		});
		// Now look for the other language elements we've found
		if (languages.length) $.each(languages, function(i, lang) {
			y.searchByAddress(result.formatted_address, function(results) {
				y.setControlsByResult(settings, results[0], true, true);
			}, lang, settings.o.region);
		});
		// If we found a zoom level, set the map
		if (zoom && !notSetMap) {
			settings.map.setCenter(result.geometry.location);
			y.actions.setZoom(settings, zoom);
		}
		return !failed;
	},
	createMarker: function(settings, center) {
		// Create the location infoDiv going together with the marker
		var info = $('<div class="yMapsAddress">')
			.css({position: 'absolute', cursor: 'pointer', top: 0, right: 0, zIndex: 100, padding: '4px 6px', backgroundColor: 'white', display: 'none'})
			.appendTo(settings.div);
		
		// If we have controls for the map, bind the click event to the address map
		if (settings.controls) info.bind('click.yMaps', function() {
			var result = info.data('location');
			if (result) y.setControlsByResult(settings, result);
		});
		
		function setInfo(result) {
			info.data('location', result);
			info.text(result.formatted_address);
			if (info.is(':hidden')) info.fadeTo(2000, settings.o.addressOpacity);
		}
		
		// When the marker position changes we call this function.
		// It checks whether the marker position is different from
		// the position that's set in it's location result stored
		// with the data function.
		function posChanged(pos) {
			var result = info.data('location');
			if ($.type(pos) != 'object') pos = marker.getPosition();
			if (pos && (!result || !pos.equals(result.geometry.location))) y.addressByLocation(pos, function(results) {
				setInfo(results[0]);
			}, settings.o.language, settings.o.region);
		}
		
		if (center.formatted_address) {
			// If the passed argument is a proper address result we can set the info text and
			result = center;
			center = center.geometry.location;
		} else {
			// Otherwise we assume the center argument is a location and we pass it to posChanged
			posChanged(center);
		}
		
		// Create the marker!
		var marker = new google.maps.Marker({
    		map: settings.map,
    		position: center,
    		draggable: true
    	});
		
		// Add binds and listeners to respond to changes
		google.maps.event.addListener(settings.map, 'dblclick', function(event) {
			// Setting the position of the marker automatically calls the 'position_changed' event.
			marker.setPosition(event.latLng);
		});
		
		settings.wrapper.bind('center_changed', function(e, result) {
			setInfo(result);
			marker.setPosition(result.geometry.location);
		});
		
		var dragging;
		
		google.maps.event.addListener(marker, 'dragstart', function() {
			dragging = true;
		});
		
		var timeout;
		google.maps.event.addListener(marker, 'position_changed', function() {
			// Don't update while dragging.. it's too expensive
			clearTimeout(timeout);
			if (dragging) timeout = setTimeout(posChanged, 500);
			else posChanged();
		});
		
		google.maps.event.addListener(marker, 'dragend', function() {
			dragging = false;
			posChanged();
		});
		
		return settings.marker;
	},
	
	actions: {
		setSearchBounds: function(settings, bounds) {
			var type = $.type(bounds);
			if (type == 'string') {
				y.searchByAddress(bounds, function(results) {
					y.actions.setSearchBounds(settings, results);
				}, settings.o.language, settings.o.region);
			} else if (type == 'array') {
				y.actions.setSearchBounds(settings, bounds[0]);
			} else {
				if (bounds.geometry) settings.bounds = bounds.geometry.bounds;
				else settings.bounds = bounds;
			}
		},
		searchByControls: function(settings, options) {
			y.searchByControls(settings, options, function(results) {
				y.actions.setCenter(settings, results);
			});
		},
		setControls: function(settings, controls) {
			settings.controls = controls;
			
			var timeout, listTimeout;
			function trigger(options) {
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					y.actions.searchByControls(settings, options);
				}, 200);
			}
			
			function inputControl(control, options) {
				control.attr('autocomplete', 'off');
				var list = $('<ul class="yMapsAutocomplete">');
				list.parents().css({zIndex: 9});
				var css = {
					display: 'none',
					position: 'absolute',
					zIndex: 10
				};
				list.css(css).insertAfter(control);
				
				var timeout, lastVal;
				control
					.bind('focus.yMapsControls', function() {
						clearTimeout(listTimeout);
					})
					.bind('blur.yMapsControls', function(e) {
						listTimeout = setTimeout(function() { list.hide(); }, 300);
						clearTimeout(timeout);
					})
					.bind('keydown.yMapsControls', function(e, extra) {
						var key = e.which;
						switch (key) {
							case y.keycodes.KEY_DOWN:
								if (list.is(':hidden')) {
									if (list.has('li')) list.fadeIn();
									else return true;
								}
								e.preventDefault();
								var li = list.children('.selected'), next;
								if (li.length) {
									next = li.next();
									if (next.length) li.removeClass('selected');
								} else next = list.children(':first');
								if (next.length) next.addClass('selected');
							break;
							case y.keycodes.KEY_UP:
								e.preventDefault();
								var li = list.children('.selected');
								if (li.length) {
									li.removeClass('selected');
									li.prev().addClass('selected');
								}
							break;
							case y.keycodes.KEY_ESC:
								list.hide();
								clearTimeout(timeout);
							break;
							case y.keycodes.KEY_ENTER:
								var li = list.children('.selected');
								if (li.length) {
									e.preventDefault();
									var result = li.data('result');
									list.empty().hide();
									y.actions.setCenter(settings, [result]);
									y.setControlsByResult(settings, result);
									lastVal = control.val();
								}
							break;
						}
					})
					.bind('keyup.yMapsControls', function(e, extra){
						if (control.val() == lastVal) return true;
						lastVal = control.val();
						if (lastVal == '') {
							list.hide();
							return true;
						}
						if (extra == 'yMaps') return true;
						clearTimeout(timeout);
						timeout = setTimeout(function() {
							y.searchByControls(settings, options, function(results) {
								list.empty();
								if (results && results.length) {
									$.each(results.slice(0,5), function(i, result) {
										var li = $('<li>')
										.text(result.formatted_address)
										.appendTo(list)
										.data('result', result)
										.hoverClass('hover')
										.bind('click', function(e) {
											e.preventDefault();
											var result = li.data('result');
											list.empty().hide();
											y.actions.setCenter(settings, [result]);
											y.setControlsByResult(settings, result);
											lastVal = control.val().get(0).focus();
										});
									});
									css = control.position();
									css.top = control.outerHeight() + css.top;
									list.css(css);
									list.fadeIn();
								}
							});
						}, 400);
					});
			}
			
			$.each(controls, function() {
				var control = this.control, options = this;
				if (control.is('select')) {
					control.bind('change.yMapsControls', function(e, extra) {
						// Don't respond to internal triggers
						if (extra == 'yMaps') return;
						trigger.call(this, options);
					});
				} else if (control.is(':text') && this.autocomplete === undefined || this.autocomplete === true) {
					inputControl(control, this);
				}
			});
		},
		setCenter: function(settings, results, fromMarker) {
			if (!results) return;
			var result = results[0];
				position = result.geometry.location;
			settings.map.panTo(position);
			settings.wrapper.trigger('center_changed', result);
			if (!fromMarker) {
				y.actions.setZoom(settings, result.address_components[0].types[0]);
			}
		},
		setZoom: function(settings, zoom) {
			if ($.type(zoom) == 'string') zoom = y.zoom[zoom] || 6;
			settings.map.setZoom(zoom);
		}
	},
	// Search by controls, if they're set.
	searchByControls: function(settings, controlOptions, callback) {
		if (!settings.controls) return false;
		// If the second parameter is a function, it's the callback.
		if ($.isFunction(controlOptions)) {
			callback = controlOptions;
			controlOptions = false;
		}
		// Set the current language we're working in.
		var address = [], lang = controlOptions && controlOptions.lang ? controlOptions.lang : settings.o.language;
		$.each(settings.controls, function() {
			var input = this.control, value;
			// Don't look at disabled / readonly inputs or inputs that have a different language set.
			if (input.is('[readonly],[disabled]') || (this.lang && this.lang != lang)) return;
			// For selects we use a different message
			if (input.is('select')) {
				if (input.val() && (value = input.children(':selected').text())) address.push(value.split(' ').shift());
			} else if (input.is('input') && (value = input.val()) && !input.is('.placeholder')) address.push(value);
		});
		// If we found an address, search for it to process
		if (address.length) {
			y.search({address: address.join(','), bounds: settings.bounds}, function(results) {
				callback(results);
			}, lang, settings.o.region);
		} else callback();
	},
	locationByAddress: function(address, callback, language, region) {
		y.search({address: address}, function(results){
			callback(results[0].geometry.location);
		}, language, region);
	},
	searchByAddress: function(address, callback, language, region) {
		y.search({address: address}, function(results){
			callback(results);
		}, language, region);
	},
	addressByLocation: function(location, callback, language, region) {
		y.search({location: location}, function(results){
			callback(results);
		}, language, region);
	},
	// The generic search function.
	search: function(options, callback, language, region) {
		options = $.extend({language: language, region: region}, options);
		y.geocoder.geocode(options, function(results, status) {
			 if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
				 // Set the language to each result for convenience.
				 $.each(results, function() {
					this.lang = language; 
				 });
				 if (callback) callback(results);
			 }
		});
	}
};
	
$.fn.yMaps = function(options, args) {
	if ($.type(options) == 'string') {
		if (y.actions[options]) {
			var settings = this.data('yMaps'), args;
			if (settings) {
				args = $.makeArray(arguments).slice(1);
				args.unshift(settings);
				return y.actions[options].apply(y, args);
			}
		}
		return false;
	}
	
	var settings = {
		o: $.extend(true, y.defaults, options)
	};
	
	if (!y.mapDefaults) y.load(settings.o, this, init); 
	else init.apply(this);
	
	function init() {
		settings.o.map = $.extend({}, y.mapDefaults, settings.o.map);
		this.each(function() {
			var self = $(this), height;
			
			// Set the wrapper and the map div... we use the the wrapper for element animations, so we can hide the
			// map as long as it's not loaded.
			settings.wrapper = self.addClass('jq_yMaps');
			settings.div = self.children('div');
			if (!settings.div.length) settings.div = $('<div>').appendTo(self);
			settings.div.addClass('yMaps');
			
			// Get the height... if set, it has priority, otherwise, try to calculate the height by the map div already
			// there, or if not there, the height of the container (the element we select in the first place)
			height = settings.o.height || settings.div.height() || self.height();
			
			settings.div.height(height);
			// Fix the width for animations and such.
			var width = self.width();
			if (width === 0 && self.is(':hidden')) {
				self.show();
				width = self.width();
				self.hide();
			}
			settings.div.width(width);
			
			if (settings.o.fx.show) self.css({display: 'block', visibility: 'hidden', position: 'absolute'});
			
			// Store the settings for later retrieval
			self.data('yMaps', settings);
			
			// Initialise the map
			y.init(settings);
		});
	}
	
	return this;
};

})(jQuery);