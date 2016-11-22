function findPrivateMailRecipients(ss) {
	var results = $('#pmrecipients_results'), timeout, anotherTimeout;
	if (!results.length) {
		results = $('<div>').attr('id', 'pmrecipients_results').appendTo($('#to').parent());
		$('#to').blur(function() {
			anotherTimeout = setTimeout(function() { results.hide(); }, 300);
		}).focus(function() { if (anotherTimeout) clearTimeout(anotherTimeout); });
	}
	if (ss.length > 2) {
		if (timeout) clearTimeout(timeout);
		timeout = setTimeout(function() {
			results.load('/en/users/pm_recipients/' + encodeURIComponent(ss), function() {
				results.stop(true, true).fadeIn();
			});
		}, 200);
	}
	else
		results.hide();
}

function addPrivateMailRecipient(user_id, nickname) {
	$('#to').val(nickname).attr('disabled', true);
	$('#pmrecipients_results').fadeOut();
	$('#pm_compose').append('<input type="hidden" name="to_id" value="' + user_id + '" />');
}

function attachInfoWindow(map, marker, name, address, listing_id, url) {
	var infowindow = new google.maps.InfoWindow;
	infowindow.setContent('<span class="map_infowindow"><a href="' + url + '"><b>' + name + '</b></a><br />' + address + '</span>');
	google.maps.event.addListener(marker, 'click', function() {
		infowindow.open(map, marker);
	});
}

function loadItemMapper(dest_element, listing_id) {

	$.getJSON('/en/listings/item_json/' + listing_id + '/',
			function(item) {
				var latlon = new google.maps.LatLng(item.lat, item.lon);
				var image = new google.maps.MarkerImage('/images/gokunming/map_pin.png',
				new google.maps.Size(13, 12));
	
				var map_options = {
					zoom: 16,
					center: latlon,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					navigationControl: true,
					mapTypeControl: false,
					scaleControl: false
				}
	
				map = new google.maps.Map(document.getElementById(dest_element), map_options);
	
				var marker = new google.maps.Marker({
					position: latlon,
					map: map,
					icon: image,
					title: item.name
				});
	
				google.maps.event.addListener(map, 'click', function(event) {
					updateMarker(marker, event.latLng, listing_id);
				});
			}
	);
}

function updateMarker(marker, location, listing_id) {
    marker.setPosition(location);
    map.setCenter(location);

	$.ajax({
	type: 'GET',
	url: '/en/listings/map_suggest/' + listing_id + '/' + location.lat() + '/' + location.lng() + '/'
	});
}

function ajax_action_categorize_add(listing_id, category_id) {
	if (category_id)
		$('#listings_chosen_categories').load('/en/listings/add_category/', 'listing_id=' + listing_id + '&category_id=' + category_id);
}

function ajax_action_categorize_remove(listing_id, category_id) {
	$('#listings_chosen_categories').load('/en/listings/remove_category/', 'listing_id=' + listing_id + '&category_id=' + category_id);
}

var windowHash = window.location.hash ? decodeURIComponent(window.location.hash) : false;
if (windowHash[1] == '.') windowHash = false;

$(function() {
	//$("#mainContent, #sidebar").stick_in_parent({offset_top: 45});
	
	if (windowHash && $(windowHash).length) {
		$.scrollTo(windowHash, {duration: 400, offset: -120, easing: 'easeOutCubic'});
	}
	
	$('#menuToggle').click(function(e) { $('html').toggleClass('js-nav'); });
	$('#cover').click(function(e) { $('html').removeClass('js-nav'); });
	
	//$('a.lightbox').attr('data-lightbox-gallery', 'blog-gallery').nivoLightbox({effect: 'fadeScale'});
	
	$('.guidetext').each(function() {
		var $this = $(this), input = $this.siblings('input, textarea');
		input.bind('focus mouseover', function() {
			$this.stop(true,true).fadeIn(300, 'easeOutCubic');
		}).bind('blur mouseout', function() {
			$this.stop(true,true).fadeOut(200, 'easeOutCubic');
		});
	});
	
	$('.tabCaptions').each(function() {
		//return;
		var $this = $(this),
			links = $this.find('a'),
			active = links.filter('.active'),
			tabContainer = $this.next().children().andSelf().filter('.tabs'),
			tabs = tabContainer.find('.tab');
		
		function updateHeight() {
			tabContainer.css({height: tabs.filter('.active').outerHeight()});
		}
		
		var resizeTimeout;
		$(window).resize(function() {
			if (resizeTimeout) clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(updateHeight, 100);
		});
		
		links.click(function(e) {
			var $this = $(this);
			if (e.metaKey) return true;
			
			if (active.length && $this != active) {
				var hash = active.removeClass('active').get(0).href.split('#')[1];
				$('#' + hash).removeClass('active');
			}
			active = $this.addClass('active');
			hash = this.href.split('#')[1];
			
			$('#' + hash).addClass('active');
			setTimeout(updateHeight, 50);
			e.preventDefault();
		});
		
		$(window).on('updateTabHeight', updateHeight);
		
		if (windowHash && links.filter('[href$=' + windowHash + ']').trigger('click').length);
		else if (active.length == 0) {
			links.filter(':first').trigger('click');	
		} else active.click();
		tabContainer.find('img').imageReady(updateHeight);
	});
	
	$('#currentEvents').find('h1.dateTitle').click(function() {
		var $this = $(this), con = $('#monthSliderContainer'), height = con.children().height();
		$this.toggleClass('active');
		con.height($this.is('.active') ? height : 0);
		return false;
	});
	
	$('time:not(.nojs)').timeago();
	
	$('#toolsMenu')
		.find('li.interactive')
			.hoverClass('hover')
			.click(function() { $(this).toggleClass('active'); });
	
	var mappie = $('#google_maps_item_large');
	var maps = $('div.google_map');
	if (maps.length || mappie.length) {
		$.getScript('http://www.google.cn/jsapi', function() {
			google.load('maps', '3', { other_params: 'sensor=false', callback: function() {
				maps.loadMap();
				if (mappie.length) loadItemMapper('google_maps_item_large', mappie.data().listingId);
			}});
		});
	}
	
	/*
	function ReinitializeAddThis(){
		if (window.addthis) {	
			window.addthis.ost = 0;
			window.addthis.ready();
		}
	}
	
	var sharing = $('div.whiteBox.sharing, #shareArticle');
	if (sharing.length) {	
		$.ajax({
			url: "http://s7.addthis.com/js/300/addthis_widget.js#pubid=ra-4fc6d6321f3f1a5a",
			dataType: 'script',
			success: function() {
				ReinitializeAddThis();
				sharing.show('fast');
			}
		});
	}
	*/
	
	var con = $('#container');
	$('#container img').imageReady(function() {
		var main = $('#mainContent'), side = $('#sidebar'), sideOffset = side.offset(), mainHeight = main.height(), sideHeight = side.height(), win = $(window);
		win.on('resize scroll', function(e) {
			var winb = win.scrollTop() + win.height() + 35;
			if (winb > sideOffset.top + sideHeight) {
				side.addClass('static-top');
			}
		});
	});
	
	$('.tooltip').attr('title', '');
});
/*
Modernizr.load({
	test: Modernizr.touch && Modernizr.csstransitions,
	yep: 'js/swipe.js',
	complete: function() {
		if (Modernizr.touch && Modernizr.csstransitions) {
			swipeEnabled = true;
			//buildSwipe();
		}
	}
});
*/
// Tracks ad links and other important clicks as events
trackAds();
function trackAds() {
	var types = {
		Ads: '.pro, #promHomeTop, #promCalendarTop',
		UpcomingSidebar: '#sidebar_upcoming',
		UpcomingEvents: '#events #sliderEvents',
		RelatedEvents: '#event #sliderEvents',
		RelatedArticles: '#related'
	};
	$.each(types, function(category, query) {
		$(query).find('a').each(function() {
			var self = $(this), tracked = false;
			//if (console && console.log) console.log(self, this);
			if (self.data('adLinkGAEvent')) return;
			else self.data('adLinkGAEvent', true);
			$(this)
				.click(function(e) {
					if (window._gaq && !tracked) {
						tracked = true;
						_gaq.push(['_trackEvent', category, 'Click', this.href]);
						if (!e.metaKey) {
							e.preventDefault();
							setTimeout('document.location = "' + this.href + '"', 300);
						}
					}
				});
		});
	});
}

// Tracks links that are going to other websites.. messy code
function trackOutgoing() {
	$('a').each(function() {
		var self = $(this), tracked = false;
		if (self.data('linkCheck')) return;
		else self.data('linkCheck', true);
		// If the link only has javascript (no http), don't track
		if (this.hostname != window.location.hostname && this.href.indexOf('http') === 0) {
			var linkHost = this.hostname.replace('www.', ''), host = window.location.hostname.replace('www.', '');
			// Only mark it as external link if it's really a different site, not just a different subdomain
			if (linkHost == host) return;
			// See if the link is an ad. If so, we also need to track the ad event
			var isAd = $(this).parent().is('#super, #ad_1, #ad_2, #ad_3');
			if (isAd) self.data('adLinkGAEvent', true);
			$(this)
				.attr('target', '_blank')
				.click(function(e) {
					if (window._gaq && !tracked) {
						tracked = true;
						// if it's an ad, also track the ad event
						if (isAd) {
							_gaq.push(['_trackEvent', 'Ads', 'Click', this.href]);
						}
						_gaq.push(['_trackPageview', '/outgoing/'+this.href]);
						if (!e.metaKey) {
							e.preventDefault();
							window.open(this.href);
						}
					}
				});
		}
	});
}

$.fn.loadMap = function() {
	return this.each(function() {
		var $this = $(this), data = $this.data();
		
		if (data.mapsPlugin) $.getScript('/js/markerclusterer.js', processMap);
		else processMap();
		
		function processMap() {
			$this.show(100, _processMap);
		}
		
		function _processMap() {
			$(window).trigger('updateTabHeight');
			
			var info = $this.find('.infoHeader'),
				listingInfo = info.find('.listing'),
				latlon = new google.maps.LatLng(data.latitude, data.longitude);
			
			google.maps.visualRefresh = true;
			
			var map_options = {
				zoom: data.zoom ? data.zoom : 13,
				center: latlon,
				panControl: true,
				zoomControl: true,
				scrollwheel: false,
				streetViewControl: false,
				zoomControlOptions: {
				  style: google.maps.ZoomControlStyle.LARGE
				},
				scaleControl: true,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			}
			
			$this.show();
			
			var map = new google.maps.Map($this.find('.mapContainer').get(0), map_options);
			var image = new google.maps.MarkerImage('/images/gokunming/map_pin.png', new google.maps.Size(10, 10));
			
			var markers = [];
			
			if (data.cityId) {
				var args = {};
				if (data.searchForm) args['search'] = $('#' + data.searchForm).find('[type=search]').val();
				$.getJSON('/en/listings/city_json/' + data.cityId + '/' + data.categoryId, args,
					function(data) {
						var bounds = new google.maps.LatLngBounds();
						
						function loadMarkerURL() {
							window.location.href = this.url;
						}
						
						$.each(data, function() {
							var listing = this;
							latlon = new google.maps.LatLng(this.lat, this.lon);
							bounds.extend(latlon);
							var marker = new google.maps.Marker({
								position: latlon,
								//icon: image,
								url: this.url,
								title: this.name,
								clickable: true
							});
							google.maps.event.addListener(marker, 'mouseover', function() {
								info.addClass('showInfo');
								var text = '<span class="listingTitle">' + listing.name + '</span>';
								if (listing.address) text = text + ' • ' + listing.address;
								listingInfo.html(text);
							});
							google.maps.event.addListener(marker, 'mouseout', function() {
								info.removeClass('showInfo');
							});
							google.maps.event.addListener(marker, 'click', loadMarkerURL);
							markers.push(marker);
							//attachInfoWindow(map, marker, this.name, this.address, this.listing_id, this.url);
						});
						
						var markerClusterer = new MarkerClusterer(map, markers, 
							{
								maxZoom: 15,
								gridSize: 45
							}
						);
						map.fitBounds(bounds);
					}
				);
			} else {
				new google.maps.Marker({
					position: latlon,
					map: map,
					clickable: false
				});
			}
		}
	});
};

function loadItemMap(dest_element, listing_id) {

	$.getJSON('/en/listings/item_json/' + listing_id + '/', {},
        function(data) {
            var latlon = new google.maps.LatLng(data.lat, data.lon);
            var image = new google.maps.MarkerImage('/images/gokunming/map_pin.png',
													new google.maps.Size(10, 10));

            var map_options = {
                zoom: 16,
                center: latlon,
                mapTypeId: google.maps.MapTypeId.ROADMAP
			}

            var map = new google.maps.Map(document.getElementById(dest_element), map_options);

            var marker = new google.maps.Marker({
                position: latlon,
                map: map,
                icon: image
            });

			google.maps.event.addListener(map, 'resize', function(event) {
				map.setCenter(latlon);
            });

        }
    );
}

function resizeItemMap(dest_element) {
	if ($('#' + dest_element).width() == 400) {
		$('#' + dest_element).width(220);
		$('#' + dest_element).height(220);
		$('#google_maps_item_control').html('Expand map');
	}
	else {
		$('#' + dest_element).width(400);
		$('#' + dest_element).height(400);
		$('#google_maps_item_control').html('Shrink map');
	}
}

function attachInfoWindow(map, marker, name, address, listing_id, url) {
	var infowindow = new google.maps.InfoWindow;
	infowindow.setContent('<article data-listing-id="' + listing_id + '" class="map_infowindow"><a href="' + url + '"><h1>' + name + '</h1></a><p>' + address + '</p></article>');
	google.maps.event.addListener(marker, 'click', function() {
		infowindow.open(map, marker);
	});
}
