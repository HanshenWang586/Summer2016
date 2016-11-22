$(function() {
	var container = $('#homeCarrousel');
	container.find('img').imageReady(function() {
	
		var items = container.find('a'),
			width = container.find('.large img:first').width(),
			height = container.find('.large img:first').height(),
			timeout;
		
		container.hover(function(e) {
			clearTimeout(timeout);
		}, function(e) {
			clearTimeout(timeout);
			timeout = setTimeout(nextItem, 2000);
		});
		
		items.mouseenter(function(e) {
			activeItem(this);
		});
		
		items.find('img').imageReady(startTimeout);
		
		function startTimeout() {
			clearTimeout(timeout);
			timeout = setTimeout(nextItem, 4000);
		}
		
		function activeItem(item) {
			items.removeClass('active');
			$(item).addClass('active');
		}
		
		function nextItem() {
			var index = items.index(items.filter('.active')) + 1;
			if (index >= items.length) index = 0;
			activeItem(items.get(index));
			startTimeout();
		}
		
		
		function resizeMe() {
			var windowWidth = $(window).width();
			if (windowWidth > 777) {
				container.css('padding', '').height('');
			} else {
				container.css('padding', 0).height(windowWidth / width * height);
			}
		}
		
		$(window).bind('resize orientationchange', resizeMe);
		resizeMe();
	});
	
	var listings = $('#homeListings');
	if (listings.length) {
		listings.find('.ListingCategories a').ajaxNav({
			ajaxParams: {view: 'getListingsBox'},
			target: listings.find('.listingsBox'),
			createWorkspace: true,
			hash: false,
			dynamicWidth: true,
			classActive: 'active',
			fixViewport: false,
			dimensional: false,
			callback: processAjax,
			beforeShow: function(target, old) {
				if (old) {
					var hash = old.find('a.active').get(0).href.split('#')[1];
					target.find("[href$='" + hash + "']").trigger('click', [true]);
				}
			}
			//onShow: function() { $(window).trigger('updateTabHeight'); }
		});
	}
});