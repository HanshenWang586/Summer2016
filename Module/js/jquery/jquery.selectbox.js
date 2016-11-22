/*
 * jQuery selectbox plugin
 *
 * Copyright (c) 2007 Sadri Sahraoui (brainfault.com)
 * Licensed under the GPL license:
 *   http://www.gnu.org/licenses/gpl.html
 *
 * The code is inspired from Autocomplete plugin (http://www.dyve.net/jquery/?autocomplete)
 *
 * Revision: $Id$
 * Version: 0.3
 */
jQuery.fn.extend({
	selectbox: function(options) {
		return this.each(function() {
			new jQuery.SelectBox(this, options);
		});
	}
});

jQuery.SelectBox = function(selectobj, options) {
	//jquery object for select element
	var $select = $(selectobj);

	if ($select.is('.jq_selectbox')) return this;
	$select.addClass('jq_selectbox');

	var opt = options || {};
	opt.inputClass = opt.inputClass || "selectbox";
	opt.containerClass = opt.containerClass || "selectbox-wrapper";
	opt.hoverClass = opt.hoverClass || "selected";
	opt.debug = opt.debug || false;
    opt.hideFirstItem = opt.hideFirstItem || false;

	var elm_id = selectobj.id;
	var active = -1;
	var inFocus = false;
	var hasfocus = 0;
	//jquery input object
	var $input = setupInput(opt);
	// jquery container object
	var $container = setupContainer(opt);
	// hide select and append newly created elements
	var triggered = false;
	var focusShow = false;
	var mouseDownBeforeFocus = false;
	$select.css('display', 'none').before($input).before($container);

	init();
	var $i = 0;
	$input
	.click(function(e){
		if (e.originalEvent != undefined){
			triggered = true;
		}
		mouseDownBeforeFocus = false;
		if (focusShow) {
			focusShow = false;
			return;
		}
		if ($container.css('display') != 'block') {
			// Yereth: Ok, we seriously need some $container positioning to enable us to
			// actually have more of these boys in one page without having to define in CSS
			// where the container is supposed to be placed
			var pos = $input.position();
			$container.css({
				position: 'absolute',
				left: pos.left,
				top: pos.top + $input.height()
			});

			$container.show();
		}
		else $container.hide();

	})
	.mousedown(function(e) {
		mouseDownBeforeFocus = true;
	})
	.focus(function(e){
	   if ($container.not(':visible')) {
		   if (mouseDownBeforeFocus) focusShow = true;
		   inFocus = true;

			// Yereth: Ok, we seriously need some $container positioning to enable us to
			// actually have more of these boys in one page without having to define in CSS
			// where the container is supposed to be placed
			var pos = $input.position();
			$container.css({
				position: 'absolute',
				left: pos.left,
				top: pos.top + $input.height()
			});
	       $container.show();
	   }
	})
	.keydown(function(event) {
		switch(event.keyCode) {
			case 38: // up
				event.preventDefault();
				moveSelect(-1);
				break;
			case 40: // down
				event.preventDefault();
				moveSelect(1);
				break;
			//case 9:  // tab
			case 13: // return
				event.preventDefault(); // seems not working in mac !
				setCurrent();
				hideMe();
				break;
		}
	})
	.blur(function() {
		if ($container.is(':visible') && hasfocus > 0 ) {
			if(opt.debug) console.log('container visible and has focus')
		} else {
			hideMe();
		}
	});


	function hideMe() {
		hasfocus = 0;
		$container.hide();
	}

	function init() {
		$container.append(getSelectOptions());
		$container.css('display', 'none');
		$container.width($input.outerWidth());
    }

	function setupContainer(options) {
		var container = document.createElement("div");
		$container = $(container);
		$container.attr('id', elm_id+'_container');
		$container.addClass(options.containerClass);

		return $container;
	}

	function setupInput(options) {
		return $('<input type="text" />').attr({
			id: elm_id + "_input",
			autocomplete: "off",
			readonly: "readonly",
			tabIndex: $select.attr("tabindex")
		}).addClass(options.inputClass);
	}


	function moveSelect(step) {
		var lis = $("li", $container);
		if (!lis) return;

		active += step;

		if (active < 0) {
			active = 0;
		} else if (active >= lis.size()) {
			active = lis.size() - 1;
		}

		lis.removeClass(opt.hoverClass);

		$(lis[active]).addClass(opt.hoverClass);
	}

	function setCurrent() {
		var li = $("li."+opt.hoverClass, $container).get(0);
		var value = li.id;
		// Trigger the change event, so we can still bind to the select!
		if ($select.val() !== value) $select.val(value).trigger('change');
		$input.val($(li).html());
		return true;
	}

	// select value
	function getCurrentSelected() {
		return $select.val();
	}

	// input value
	function getCurrentValue() {
		return $input.val();
	}

	function getSelectOptions() {
		var select_options = new Array(), ul = document.createElement('ul'), tempVal, li, css = {};
		$select.children('option').each(function(i) {
            var self = $(this);
            if(!opt.hideFirstItem||i>0) {
                li = document.createElement('li');
                li.setAttribute('id', self.val());
                li.innerHTML = self.html();
                if (self.is(':selected')) {
                    $input.val(self.html());
                    $(li).addClass(opt.hoverClass);
                }

                $.each('backgroundScroll backgroundPosition backgroundImage backgroundRepeat'.split(' '), function() {
                    if (tempVal = self.css(this.toString())) css[this] = tempVal;
                });
                if(opt.debug) console.log(css);

                ul.appendChild(li);
                $(li)
                    .css(css)
                    .mouseover(function(event) {
                        hasfocus = 1;
                        if (opt.debug) console.log('out on : '+this.id);
                        $(event.target, $container).addClass(opt.hoverClass);
                    })
                    .mouseout(function(event) {
                        hasfocus = -1;
                        if (opt.debug) console.log('out on : '+this.id);
                        $(event.target, $container).removeClass(opt.hoverClass);
                    })
                    .click(function(event) {
                        if (opt.debug) console.log('click on :'+this.id);
                        $(this).addClass(opt.hoverClass);
                        setCurrent();
                        hideMe();
                    });
            } else {$input.val(self.html());}
		});
		return ul;
	}
};