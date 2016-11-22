(function($) {
  
  $.fn.fmtag = function(settings) {
    
    if (settings) {
      $.extend($.fn.fmtag.defaults, settings);
    }
    
    this.each(function() {
      var elem = $(this), tagInput,
	      resListContainer = $("<div>").addClass("fmtag-results"),
	      resList = $("<ul>"),
	      Event = {
	    	  KEY_BACKSPACE : 8,
	    	  KEY_DELETE	: 46,
	    	  KEY_ENTER     : 13,
	    	  KEY_ESC       : 27,
	    	  KEY_SPACE     : 32,
	    	  KEY_LEFT      : 37,
	    	  KEY_UP        : 38,
	    	  KEY_RIGHT     : 39,
	    	  KEY_DOWN      : 40,
	    	  KEY_COMMA		: 188
	      },
	      tagList = [],
	      inputName;
      
      resListContainer.append(resList);
      
      if (elem.is('input')) {
    	  inputName = elem.attr('name') + '[]';
    	  tagInput = elem.attr('size', 1).removeAttr('name');
    	  elem = tagInput.wrap('<div>').parent();
      } else {
    	  tagInput = $("<input>").attr({ type: "text", size: "1" });
    	  inputName = 'tags[]';
    	  elem.append(tagInput);
      }
      
      elem.addClass("fmtag").bind("click", function(e) {
        tagInput.focus();
      });
      
      elem.after(resListContainer);
      
      var tempVal = tagInput.val();
      if (tempVal) {
    	  $.each(tempVal.split(','), function() {
    		  createTag($.trim(this), true);
    	  });
      }
      
      elem.delegate('.fmtag-tag.selected', 'blur', function(e) {
    	  $(this).removeClass('selected');
      });
      
      elem.delegate('.fmtag-tag', 'keydown', function(e) {
    	  var key = e.which, self = $(this);
		  switch (key) {
		  	  case Event.KEY_DELETE:
			  case Event.KEY_BACKSPACE:
				  e.preventDefault();
				  removeTag(self);
				  tagInput.focus();
			  break;
			  case Event.KEY_LEFT:
				  var tagAnchor = self.prev('.fmtag-tag');
				  if (tagAnchor.length) {
		              tagAnchor.addClass("selected").focus();
		              self.removeClass('selected');
				  }
	          break;
			  case Event.KEY_RIGHT:
				  var next = self.next();
				  if (next.is('input')) next.focus();
				  else if (next.is('.fmtag-tag')) next.addClass('selected').focus();
				  self.removeClass('selected');
			  break;
		  }
      });
      
      elem.delegate('.fmtag-remove', 'click', function(e) {
    	  e.stopPropagation();
    	  removeTag($(this).closest(".fmtag-tag"));
      });
      
      elem.delegate('.fmtag-tag', 'click', function(e) {
          $(this).addClass("selected").focus().siblings('.fmtag-tag').removeClass('selected');
      });
      
      tagInput.bind("keydown", function(e) {
        var key = e.which;
        switch (key) {
          case Event.KEY_COMMA:
            e.preventDefault();
        	var value = resListContainer.is(":visible") ? resList.find("li.selected").text() : tagInput.val();
            hideAutocompleteResults();
            if ($.trim(value).length > 0) createTag(value);
            break;
          case Event.KEY_BACKSPACE:
          case Event.KEY_LEFT:
            if (tagInput.val() == "") {
        	  e.preventDefault();
              var tagAnchor = tagInput.prev(".fmtag-tag");
              if (tagAnchor.length) tagAnchor.addClass("selected").focus();
            }
            break;
          case Event.KEY_UP:
          case Event.KEY_DOWN:
            e.preventDefault();
            if (resListContainer.is(":visible")) {
              var numListElems = resList.children().length;
              var selListElem = resList.find("li.selected");                   
              var listElemIndex = selListElem.data("listElemIndex");
              if (listElemIndex < (numListElems-1) && key == Event.KEY_DOWN) {
                listElemIndex += 1;
              }
              else if (listElemIndex > 0 && key == Event.KEY_UP) {
                listElemIndex -= 1;
              }

              selListElem.removeClass("selected");
              resList.children().eq(listElemIndex)
                .addClass("selected")
                .data("listElemIndex", listElemIndex);
            }
            break;
          case Event.KEY_ESC:
            hideAutocompleteResults();
            break;
          default:
        	  // Auto width of the input field
        	  var tempSpan = $('<span>').css('visibility', 'hidden').text(tagInput.val()).insertAfter(tagInput);
          	  tagInput.width(tempSpan.width() + 20);
          	  tempSpan.remove();
            break;
        }
      })
      .bind("keyup", function(e) {
        var key = e.keyCode;
        hideAutocompleteResults();
        var entry = $(this).val();
        if (entry.length > 0) {
          var res = $.grep($.fn.fmtag.defaults.autocompleteData, function(data) {
            var regex = new RegExp("^" + entry, "g");
            return data.match(regex);
          });

          if (res.length > 0) {
            $.each(res, function(idx, value) {
              var listElem = $("<li><a href='#'>" + value + "</a></li>")
                .data("listElemIndex", idx)
                .click(function(e) {
                  listElem = $(this);
                  createTag(listElem.children("a").text());
                  hideAutocompleteResults();
                });
              
              if (idx == 0) {
                listElem.addClass("selected");
              }
              resList.append(listElem);
            });

            resListContainer.show();
          }
        }
      })
      .bind("focus", function(e) {
        tagInput.data("hasFocus", true).parent().find(".fmtag-tag").removeClass("selected");
      });
      
      // function: createTag
      function createTag(value, noFocus) {
        var tagAnchor = null;
        value = $.trim(value);
        if (!value) return;
        if ($.inArray(value, tagList) == -1) {
          tagAnchor = $("<span>").attr({tabIndex: -1}).addClass("fmtag-tag");
          
          tagAnchor
          	.append($("<span>").text(value))
          	.append($("<span>").addClass("fmtag-remove"))
          	.append($('<input>').attr({type: 'hidden', name: inputName, value: value}));
          
          tagList.push(value);
          $.fn.fmtag.defaults.onChange(tagList);
          
          tagInput.before(tagAnchor);
        }
        tagInput.val("");
        if (!noFocus) tagInput.focus();
      }
      
      function removeTag(tagAnchor) {
        tagAnchor.remove();
        
        var idx = -1;
        var value = tagAnchor.children("span").text();
        if ((idx = $.inArray(value, tagList)) != -1)
        {
          tagList = $.grep(tagList, function(data) {
            return (data != value);
          });
        }
        
        $.fn.fmtag.defaults.onChange(tagList);
      }
      
      // function: hideAutocompleteResults
      function hideAutocompleteResults() {
        resList.html("");
        resListContainer.hide();
      }
      
    });
    
    return this;
    
  },
  
  $.fn.fmtag.defaults = {
    
    // Autocomplete data
    autocompleteData: [],
    
    // Callbacks
    onChange        : function() {}
    
  };
  
})(jQuery);
