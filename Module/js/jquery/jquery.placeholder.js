/*
* Placeholder plugin for jQuery
* ---
* Copyright 2010, Daniel Stocks (http://webcloud.se)
* Released under the MIT, BSD, and GPL Licenses.
* ---
* Improvements by Yereth Jansen (http://www.yereth.nl)
*/
(function($, undefined) {
	// Check for support
    var isInputSupported = 'placeholder' in document.createElement('input'),
	    isTextareaSupported = 'placeholder' in document.createElement('textarea');
    
    if (isInputSupported && isTextareaSupported) {
    	$.fn.placeholder = function() { return this; };
    	return false;
    }
	
    function Placeholder(input) {
        this.input = input;
		// Store the element itself for easier access
        this.elem = input[0];
        if (input.attr('type') == 'password') {
            this.handlePassword();
        }
    }
    Placeholder.prototype = {
        show : function(loading) {
    	// We don't want to show the placeholder if the element is in focus
    		if (this.input.is('.focus')) return;
            // FF and IE saves values when you refresh the page. If the user refreshes the page with
            // the placeholders showing they will be the default values and the input fields won't be empty.
            if (this.elem.value === '') {
                if (this.isPassword) {
                    try {
                        this.elem.setAttribute('type', 'text');
                    } catch (e) {
                        this.input.before(this.fakePassword.show()).hide();
                    }
                }
                this.input.addClass('placeholder');
                this.elem.value = this.input.attr('placeholder');
            }
        },
        hide : function() {
            if (this.valueIsPlaceholder() && this.input.hasClass('placeholder')) {
                this.input.removeClass('placeholder');
                this.elem.value = '';
                if (this.isPassword) {
                    try {
                        this.input[0].setAttribute('type', 'password');
                    } catch (e) { }
                    // Restore focus for Opera and IE
                    this.input.show();
                    this.elem.focus();
                }
            }
        },
        valueIsPlaceholder : function() {
            return this.elem.value == this.input.attr('placeholder');
        },
        handlePassword: function() {
            var input = this.input;
            input.attr('realType', 'password');
            this.isPassword = true;
            // IE < 9 doesn't allow changing the type of password inputs
            if ($.browser.msie && this.elem.outerHTML) {
                var fakeHTML = this.elem.outerHTML.replace(/type=(['"])?password\1/gi, 'type=$1text$1');
                this.fakePassword = $(fakeHTML).val(input.attr('placeholder')).addClass('placeholder').focus(function() {
                    input.trigger('focus');
                    $(this).hide();
                });
            }
        }
    };
    
    // Only create a working placefolder plugin if there's no native placeholder support
	// Create function based on support
    $.fn.placeholder = function(options) {
    	
        return this.each(function() {
            var input = $(this);
            // We don't want to process supported elements, nor elements without placeholder information, nor non-input fields
            if (
            	input.is('jq_placeholder') ||
            	(isTextareaSupported && input.is('textarea')) ||
            	(isInputSupported && input.is('input')) ||
            	input.not('input[placeholder],textarea[placeholder]').length
            ) return;
            
            // Add events and a class to mark the element. We don't want to double up. Add focus class
			// for so we don't add placeholder values for elements that are in focus
            input
            	.focus(function() {
	            	input.addClass('focus');
	            	placeholder.hide();
	            })
	            .blur(function() {
	            	input.removeClass('focus');
	            	placeholder.show(false);
	            })
	            .addClass('jq_placeholder');
            
            var placeholder = new Placeholder(input);
            // Store the placeholder in the element, so we can easily obtain them on form submits
            input.data('placeholderObj', placeholder);
            
            placeholder.show(true);
            
            // On page refresh, IE doesn't re-populate user input
            // until the window.onload event is fired.
            if ($.browser.msie) {
                $(window).load(function() {
                    if(input.val()) {
                        input.removeClass("placeholder");
                    }
                    placeholder.show(true);
                });
                // What's even worse, the text cursor disappears
                // when tabbing between text inputs, here's a fix
                input.focus(function() {
                    if(this.value == "") {
                        var range = this.createTextRange();
                        range.collapse(true);
                        range.moveStart('character', 0);
                        range.select();
                    }
                });
            }
        });
    };
    
    // Now fix the $.fn.val function cause we don't want placeholder values to influence the workings of the app
    var oldVal = $.fn.val;
    
    $.fn.val = function(val) {
    	var elems, result;
    	// Remove all placeholder texts, as we don't want real values to show as placeholders, or get
    	// placeholder values as return values
    	this.filter('.placeholder').each(function() {
			$(this).data('placeholderObj').hide();
		});
    	// Apply the old val function
    	result = val !== undefined ? oldVal.call(this, val) : oldVal.call(this);
    	// If no value was given (and the inputs might be empty), show the placeholder text again
    	if (!val) {
    		this.filter('.jq_placeholder').each(function() {
    			$(this).data('placeholderObj').show(false);
    		});
    	}
    	return result;
    };
    
    // Prevent placeholder values from submitting. We only have to bind this event once and just in case, we'll bind
    // all forms.
    $('form').submit(function() {
        var form = $(this),
        	inputs = form.one('submitFailed', function(e) {
	        	inputs.each(function(e) {
	            	var obj = $(this).data('placeholderObj');
	            	if (obj) obj.show(false);
	            });
	        }).find(':input.jq_placeholder').each(function(e) {
	        	var obj = $(this).data('placeholderObj');
	        	if (obj) obj.hide();
	        });
    });
})(jQuery);
