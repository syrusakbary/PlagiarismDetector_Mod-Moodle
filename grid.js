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
            preventDefault: true
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
			event.data.scrollable.scrollLeft(
							event.data.scrollable.scrollLeft() - delta.left);
			event.data.scrollable.scrollTop(
							event.data.scrollable.scrollTop() - delta.top);
			
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
                    preventDefault : settings.preventDefault }
		// Set mouse initiating event on the desired descendant
		$(this).find(settings.dragSelector).
						bind('mousedown', data, dragscroll.mouseDownHandler);
	});
	return this;
}; //end plugin dragscrollable

})( jQuery ); // confine scope

$(document).ready(function() {
		var top = $('.wrapper .topr .rowcol span').max($.max.innerWidth)+10;
		var left = $('.wrapper .left .row span').max($.max.innerWidth)+40;
		var width = $('.wrapper .content>div').innerWidth(true);
		var height = $('.wrapper .content>div').innerHeight(true);
		width=width>500?500:width;
		height=height>400?400:height;
		//
		top = left;
	$('.wrapper').css({paddingBottom: top,paddingLeft: left,width:width+10,height:height+10});	
	
	$('.wrapper .topr').css({/*marginTop:-top,*/height: top-5});
	$('.wrapper .left').css({marginLeft:-left+5,width: left-5});
	
	$('.wrapper .content').dragscrollable({acceptPropagatedEvent: true});
	$('.wrapper .left, .wrapper .topr').dragscrollable({acceptPropagatedEvent: true});
	$('.wrapper .content, .wrapper .left').scrollsync({axis : 'y'});
	$('.wrapper .content, .wrapper .topr').scrollsync({axis : 'x'});
	$(".wrapper .content").hover(function() {
		$('.wrapper .left span, .wrapper .topr span').animate({opacity:0.5},200);
	},function() {
		$('.wrapper .left span, .wrapper .topr span').animate({opacity:1},200);
	});
	
	$(".wrapper .content .rowcol").hover( function(){
		var col = $(this).parent().find(".rowcol").index(this);
		var a = $(this).find('a').attr('class');
		a = a.match(/scale[0-9]/gi)
		$(".wrapper .mapping a[class="+a+"]").parent().find('span').addClass('over');
		var row = $(".wrapper .content .row").index($(this).parent());
		
		$('.wrapper .left .row').eq(row).find('span').addClass('over');

		
		$('.wrapper .topr .rowcol').eq(col).find('span').addClass('over');
		//$(this).find("span").addClass('over');
		//alert(col+"-"+row);
	},function () {
		$(".wrapper .mapping span").removeClass('over');
		$('.wrapper .left .row span, .wrapper .topr .rowcol span').removeClass('over');
	}).find('a').bind('mousedown',function() {
		if ($('.prewrapper').is('.editable')) {
			var title =  $(this).attr('title');
			$('.wrapper .content .rowcol a[title="'+title+'"]').toggleClass('confirmed');
		}
	}).click(function(){/*if ($('.prewrapper').is('.editable'))*/ return false;});
	$('.prewrapper button.edit').click(function()  {
		$('.prewrapper').addClass('editable');
		return false;
	});

	 //alert($(".wrapper .content .rowcol a.confirmed").size());
	 
	 $('.prewrapper button.save').click(function()  {
			var confirmed=[];
			var title;
			 $(".wrapper .content .rowcol a.confirmed").each(function() {
				 title = $(this).attr('title');
				 //alert($.inArray(title, confirmed));
				 if ($.inArray(title, confirmed) == -1) {
					 //alert('a');
					 confirmed.push(title);
				 }
				//alert();
				//confirmed += $(o).id(); 
			 });
			 $('.prewrapper input[name="confirmed"]').val(confirmed.join(','));
			 //alert(confirmed.join(','));
		$('.prewrapper').removeClass('editable');
		
		$('#plagiarismsave').submit();
		return false;
	});
	});

