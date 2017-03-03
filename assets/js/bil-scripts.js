/*
 * BetterImageLoading v0.3
 *
 * Author: Moosch Media
 *
 * Licensed GPLv3 for open source use
 *
 * http://wp.mooschmedia.com
 * Copyright 2017 BetterImageLoading
 */
+(function( win, doc ){

	"use strict";

	var BetterLoader = (function() {

		return {
			getRelativePosition: function( element ){
				// Need to account for margins and padding top
				var theCSSprop = window.getComputedStyle(element, null);
				var top = element.offsetTop;
				top -= theCSSprop.marginTop;
				top -= theCSSprop.paddingTop;
				return {
					x: element.offsetLeft,
					y: top
				};
			},
			startLoad: function( element ){;

				var noscript = element.getElementsByTagName('noscript')[0];
				var blurred = element.getElementsByTagName('img')[0];

				// Get the ratio for image set sizes
				var attWidth = blurred.getAttribute('width'),
					attHeight = blurred.getAttribute('height');
				var ratio = attWidth / attHeight;
				var scaledHeight = blurred.clientWidth/ratio;

				// Resize blurred image to match full size height
				blurred.style.height = scaledHeight + 'px';

				// Get blurred relative position
				var pos = this.getRelativePosition( blurred );

				// Allowable attributes
				var atts = [
					{key:'alt', value:'alt'},
					{key:'title', value:'title'},
					{key:'data-srcset', value:'srcset'},
					{key:'data-sizes', value:'sizes'},
					{key:'data-full', value:'full'},
					{key:'width', value:'width'},
					// {key:'data-width', value:'width'},
					{key:'height', value:'height'},
					// {key:'data-height', value:'height'},
				];

				var imgLarge = new Image();

				for( var i = 0; i < atts.length; i++ ){
					var att = blurred.getAttribute(atts[i].key);
					if( att !== null )
						imgLarge.setAttribute(atts[i].value, att);
				}

				// Add full sized image source
				imgLarge.src = blurred.dataset.full;

				// Set all blurred classes
				imgLarge.className = blurred.className;

				// Switch out classes for full sized ones
				imgLarge.classList.remove('bil-init');
				imgLarge.classList.add('bil-full-size');

				imgLarge.style.top = pos.y + 'px';
				imgLarge.style.left = pos.x + 'px';

				// Resize
				// imgLarge.style.width = blurred.clientWidth + 'px';
				// imgLarge.style.height = scaledHeight + 'px';

				// Set parent of blurred image in which to insert new image
				var par = blurred.parentNode;

				imgLarge.onload = function(){

					par.insertBefore(imgLarge, par.childNodes[0]);

					// Remove small after delay to prevent 'blink'
					setTimeout(function(){
						BetterLoader.switchToLarge( {blurred: blurred, large: imgLarge} );
					},500);

					// Finish up by removing blurred and repositioning the large image (delay to allow for css animation)
					setTimeout(function(){
						BetterLoader.finishUp( {blurred: blurred, large: imgLarge} );
					},1000);
				};

			},
			switchToLarge: function( els ){

				// Fade in large image
				els.large.classList.add('bil-loaded');
				// Fade blurred out
				els.blurred.classList.add('bil-fadeout');

			},
			finishUp: function( els ){

				// Remove position absolute
				els.large.classList.add('bil-in-position');

				els.large.removeAttribute('style');

				// remove blurred image (accessibility?)
				els.blurred.parentElement.removeChild(els.blurred);

			},
			init: function( elements ){

				if( typeof elements !== 'object' || elements.length === 0 )
					return false;

				for( var index = 0; index < elements.length; index++ ){
					// Create the wrapper
					var wrapper = document.createElement("div");
					wrapper.className = 'bil-container';

					// Add the image to the wrapper
					var node = elements[index].outerHTML;

					// Add noscript
					node = node + '<noscript>'+node+'</noscript>';
					wrapper.innerHTML = node;

					// Insert the wrapper at the image porisiont
					elements[index].parentNode.insertBefore(wrapper, elements[index]);

					// Remove the original image
					elements[index].parentNode.removeChild(elements[index]);

					// Initialise the image
					BetterLoader.startLoad( wrapper );
				}

			}

		};

	}());

	// win.onload = function() {
	// 	BetterLoader.init( doc.querySelectorAll('.bil-init') );
	// }
	/*
	NOTE: Minor glitch is if the image width is considerably more than available space.
	The height of the blurred image needs to be scaled to the width/height ratio and this may produce a little 'jank' :( 
	So to minimise this I removed win.onload wrapper function. JS is loaded in footer so will still find tha elements
	*/
	BetterLoader.init( doc.querySelectorAll('.bil-init') );

})( window, document );
