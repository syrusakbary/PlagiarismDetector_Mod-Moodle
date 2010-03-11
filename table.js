
;(function($){ // secure $ jQuery alias

/**
 * Synchronizes scroll of one element (first matching targetSelector filter)
 * with all the rest meaning that the rest of elements scroll will follow the 
 * matched one.
 * 
 * options is composed of the following properties:
 *	------------------------------------------------------------------------
 *	targetSelector	| A jQuery selector applied to filter. The first element of
 *					| the resulting set will be the target all the rest scrolls
 *					| will be synchronised against. Defaults to ':first' which 
 *					| selects the first element in the set.
 *	------------------------------------------------------------------------
 *	axis			| sets the scroll axis which will be synchronised, can be
 *					| x, y or xy. Defaults to xy which will synchronise both.
 *	------------------------------------------------------------------------
 */
$.fn.scrollsync = function( options ){
	var settings = $.extend({axis: 'xy'},options || {});
	
	function scrollHandler(event) {
		var followers = event.data.target.not(this);
		//alert(followers.size());
		if (event.data.xaxis){
			followers.scrollLeft($(this).scrollLeft());
		}
		if (event.data.yaxis){
			followers.scrollTop($(this).scrollTop());
		}
	}
	
	settings.target=this; // the rest of elements

	// Parse axis
	settings.xaxis= settings.axis.indexOf("x")!=-1; 
	settings.yaxis= settings.axis.indexOf("y")!=-1;
	if (!(settings.xaxis || settings.yaxis)) return;  // No axis left 
	
	// bind scroll event passing array of followers
	settings.target.bind('scroll', settings, scrollHandler);
	return this;
	
}; // end plugin scrollsync

})( jQuery ); // confine scope
/*
 * jQuery scrollsync Plugin
 * version: 1.0 (30 -Jun-2009)
 * Copyright (c) 2009 Miquel Herrera
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 */
;(function($){ // secure $ jQuery alias
$.max = {};

$.max.width = function (element) {
	return element.width();
}
$.max.innerWidth = function (element) {
	return element.innerWidth(true);
}
$.fn.max = function( get ){
	var max = 0;
	var m;
	this.each(function(){
		m = get($(this));
		if (m > max) max = m;
	});
	return max;
};

})( jQuery ); // confine scope

/*
 * jQuery dragscrollable Plugin
 * version: 1.0 (25-Jun-2009)
 * Copyright (c) 2009 Miquel Herrera
 *var a = $(this).find('a');
		alert(a.attr('class'));
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 */
;(function($){ // secure $ jQuery alias

/**
 * Adds the ability to manage elements scroll by dragging
 * one or more of its descendant elements. Options parameter
 * allow to specifically select which inner elements will
 * respond to the drag events.
 * 
 * options properties:
 * ------------------------------------------------------------------------		
 *  dragSelector         | jquery selector to apply to each wrapped element 
 *                       | to find which will be the dragging elements. 
 *                       | Defaults to '>:first' which is the first child of 
 *                       | scrollable element
 * ------------------------------------------------------------------------		
 *  acceptPropagatedEvent| Will the dragging element accept propagated 
 *	                     | events? default is yes, a propagated mouse event 
 *	                     | on a inner element will be accepted and processed.
 *	                     | If set to false, only events originated on the
 *	                     | draggable elements will be processed.
 * ------------------------------------------------------------------------
 *  preventDefault       | Prevents the event to propagate further effectivey
 *                       | dissabling other default actions. Defaults to true
 * ------------------------------------------------------------------------
 *  
 *  usage examples:
 *
 *  To add the scroll by drag to the element id=viewport when dragging its 
 *  first child accepting any propagated events
 *	$('#viewport').dragscrollable(); 
 *
 *  To add the scroll by drag ability to any element div of class viewport
 *  when dragging its first descendant of class dragMe responding only to
 *  evcents originated on the '.dragMe' elements.
 *	$('div.viewport').dragscrollable({dragSelector:'.dragMe:first',
 *									  acceptPropagatedEvent: false});
 *
 *  Notice that some 'viewports' could be nested within others but events
 *  would not interfere as acceptPropagatedEvent is set to false.
 *		
 */
$.fn.dragscrollable = function( options ){
   
	var settings = $.extend(
		{   
			dragSelector:'>:first',
			acceptPropagatedEvent: true,
            preventDefault: true,
            		scroll:'xy'
		},options || {});
	 
	
	var dragscroll= {
		mouseDownHandler : function(event) {
			// mousedown, left click, check propagation
			if (event.which!=1 ||
				(!event.data.acceptPropagatedEvent && event.target != this)){ 
				return false; 
			}
			
			// Initial coordinates will be the last when dragging
			event.data.lastCoord = {left: event.clientX, top: event.clientY}; 
		
			$.event.add( document, "mouseup", 
						 dragscroll.mouseUpHandler, event.data );
			$.event.add( document, "mousemove", 
						 dragscroll.mouseMoveHandler, event.data );
			if (event.data.preventDefault) {
                event.preventDefault();
                return false;
            }
		},
		mouseMoveHandler : function(event) { // User is dragging
			// How much did the mouse move?
			var delta = {left: (event.clientX - event.data.lastCoord.left),
						 top: (event.clientY - event.data.lastCoord.top)};
			
			// Set the scroll position relative to what ever the scroll is now
			if (event.data.scroll.indexOf("x")!=-1) {
			event.data.scrollable.scrollLeft(
							event.data.scrollable.scrollLeft() - delta.left);
			}
			if (event.data.scroll.indexOf("y")!=-1) {
			event.data.scrollable.scrollTop(
							event.data.scrollable.scrollTop() - delta.top);
			}
			// Save where the cursor is
			event.data.lastCoord={left: event.clientX, top: event.clientY}
			if (event.data.preventDefault) {
                event.preventDefault();
                return false;
            }

		},
		mouseUpHandler : function(event) { // Stop scrolling
			$.event.remove( document, "mousemove", dragscroll.mouseMoveHandler);
			$.event.remove( document, "mouseup", dragscroll.mouseUpHandler);
			if (event.data.preventDefault) {
                event.preventDefault();
                return false;
            }
		}
	}
	
	// set up the initial events
	this.each(function() {
		// closure object data for each scrollable element
		var data = {scrollable : $(this),
					acceptPropagatedEvent : settings.acceptPropagatedEvent,
                    preventDefault : settings.preventDefault,scroll : settings.scroll }
		// Set mouse initiating event on the desired descendant
		$(this).find(settings.dragSelector).
						bind('mousedown', data, dragscroll.mouseDownHandler);
	});
	return this;
}; //end plugin dragscrollable

})( jQuery ); // confine scope

$(document).ready(function() {
	var	m=17,
		wrapper = $('.wrapper'),
		content = $('.wrapper.content'),
		left =  $('.wrapper.left'),
		bottom =  $('.wrapper.bottom'),
		content_w = content.innerWidth(true),
		content_h = content.innerHeight(true);
	content.dragscrollable({acceptPropagatedEvent: true});
	left.dragscrollable({acceptPropagatedEvent: true,scroll:'y'});
	var	bottom_table = bottom.find('table'),
		bottom_w = bottom_table.width(),
		bottom_h = bottom_table.height();
	var	left_table = left.find('table'),
		left_w = left_table.width(),
		left_h = left_table.height();
	bottom.dragscrollable({acceptPropagatedEvent: true,scroll:'x'}).css({marginLeft:bottom_w+m+2});
	bottom.width(content_w).find('.format').height(bottom_w+m).width(bottom_h);
	left.height(content_h).find('table').width(left_w+m);
	content.hover(function() {
		bottom.find('td').stop().animate({opacity:0.5},100);
		left.find('td').stop().animate({opacity:0.5},100);
	},
	function() {
		bottom.find('td').removeClass('over').stop().animate({opacity:1},100);
		left.find('td').removeClass('over').stop().animate({opacity:1},100);
		$(".wrapper.legend table td").removeClass('over');
	});
	function getCol (elem) {
		return $(elem).parent().find('td').index(elem);
	}
	function getRow (elem) {
		return content.find('tr').index($(elem).parent());
	}		
	content.find('td').hover(function() {
		var col = getCol(this);
		var row = getRow(this);
		bottom.find('tr').eq(col).find('td').addClass('over');
		left.find('tr').eq(row).find('td').addClass('over');
		
		var level = $(this).find('a').attr('class');
		level = level.match(/level[0-9]/gi)
		$(".wrapper.legend table td a[class="+level+"]").parent().addClass('over');
	},
	function() {
		var col = getCol(this);
		var row = getRow(this);
		bottom.find('tr').eq(col).find('td').removeClass('over');
		left.find('tr').eq(row).find('td').removeClass('over');
		$(".wrapper.legend table td").removeClass('over');
	});
	$('.wrapper.content, .wrapper.left').scrollsync({axis : 'y'});
	$('.wrapper.content, .wrapper.bottom').scrollsync({axis : 'x'});
  });
