'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

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
+function (win, doc) {
	/**
  * Provides requestAnimationFrame in a cross browser way.
  * @author paulirish / http://paulirish.com/
  */

	if (!win.requestAnimationFrame) {

		win.requestAnimationFrame = function () {

			return win.webkitRequestAnimationFrame || win.mozRequestAnimationFrame || win.oRequestAnimationFrame || win.msRequestAnimationFrame || function ( /* function FrameRequestCallback */callback, /* DOMElement Element */element) {

				win.setTimeout(callback, 1000 / 60);
			};
		}();
	}

	var images = [];
	var ramChecker = void 0;

	var BetterLoader = function () {

		return {
			startLoad: function startLoad(element) {
				;

				var noscript = element.getElementsByTagName('noscript')[0];
				var blurred = element.getElementsByTagName('img')[0];

				// Get the ratio for image set sizes
				var attWidth = blurred.getAttribute('width'),
				    attHeight = blurred.getAttribute('height');
				var ratio = attWidth / attHeight;
				var scaledHeight = blurred.clientWidth / ratio;

				// Resize blurred image to match full size height
				blurred.style.height = scaledHeight + 'px';

				// Allowable attributes
				var atts = [{ key: 'alt', value: 'alt' }, { key: 'title', value: 'title' }, { key: 'data-srcset', value: 'srcset' }, { key: 'data-sizes', value: 'sizes' }, { key: 'data-full', value: 'full' }, { key: 'width', value: 'width' },
				// {key:'data-width', value:'width'},
				{ key: 'height', value: 'height' }];

				var largeImage = doc.createElement('img');

				largeImage.className = blurred.className + ' bil-full-size';

				for (var i = 0; i < atts.length; i++) {
					var att = blurred.getAttribute(atts[i].key);
					if (att !== null) largeImage.setAttribute(atts[i].value, att);
				}

				var par = blurred.parentNode;
				par.insertBefore(largeImage, par.childNodes[0]);

				var downloadingImage = new Image();

				downloadingImage.onload = function () {
					largeImage.src = this.src;

					BetterLoader.switchToLarge({ blurred: blurred, large: largeImage });

					var timer = void 0;
					timer = setTimeout(function () {
						BetterLoader.finishUp({
							blurred: blurred,
							large: largeImage,
							noscript: noscript
						});
						clearTimeout(timer);
						// Remove image
						downloadingImage.remove();
					}, 1000);
				};

				downloadingImage.src = blurred.dataset.full;

				return;
			},
			switchToLarge: function switchToLarge(els) {
				// Fade in large image
				els.large.classList.add('bil-loaded');
				// Fade blurred out
				els.blurred.classList.add('bil-fadeout');

				return;
			},
			finishUp: function finishUp(els) {
				// Remove noscript
				els.blurred.parentElement.removeChild(els.noscript);
				// Remove blurred
				els.blurred.parentElement.removeChild(els.blurred);
				// Remove image class
				els.large.classList.remove('bil-init');
				els.large.classList.remove('bil-full-size');
				els.large.classList.remove('bil-loaded');
				// Remove parent class
				els.large.parentElement.classList.remove('bil-container');

				return;
			},
			initLoading: function initLoading(image) {
				// Create the wrapper
				var wrapper = doc.createElement("div");
				wrapper.className = 'bil-container';

				// Add the image to the wrapper
				var node = image.outerHTML;

				// Add noscript
				node = node + '<noscript>' + node + '</noscript>';
				wrapper.innerHTML = node;

				// Insert the wrapper at the image porisiont
				image.parentNode.insertBefore(wrapper, image);

				// Remove the original image
				image.parentNode.removeChild(image);

				// Initialise the image
				BetterLoader.startLoad(wrapper);

				return;
			},
			checkInView: function checkInView(el) {
				var top = el.offsetTop;
				var left = el.offsetLeft;
				var width = el.offsetWidth;
				var height = el.offsetHeight;

				while (el.offsetParent) {
					el = el.offsetParent;
					top += el.offsetTop;
					left += el.offsetLeft;
				}

				return top < win.pageYOffset + win.innerHeight && left < win.pageXOffset + win.innerWidth && top + height > win.pageYOffset && left + width > win.pageXOffset;
			},
			checkImages: function checkImages() {
				if (images.length == 0) {
					win.cancelAnimationFrame(ramChecker);
					return false;
				}
				images = images.filter(function (image) {
					var inView = BetterLoader.checkInView(image);
					if (inView) {
						BetterLoader.initLoading(image);
					}
					return !inView;
				});
				ramChecker = win.requestAnimationFrame(BetterLoader.checkImages);

				return;
			},
			init: function init(elements) {
				if ((typeof elements === 'undefined' ? 'undefined' : _typeof(elements)) !== 'object' || elements.length === 0) {
					return false;
				}
				// Convert nodeList (elements) to array
				images = [].slice.call(elements);
				// checkImages
				ramChecker = win.requestAnimationFrame(BetterLoader.checkImages);

				return;
			}

		};
	}();

	// win.onload = function() {
	// 	BetterLoader.init( doc.querySelectorAll('.bil-init') );
	// }
	/*
 NOTE: Minor glitch is if the image width is considerably more than available space.
 The height of the blurred image needs to be scaled to the width/height ratio and this may produce a little 'jank' :(
 So to minimise this I removed win.onload wrapper function. JS is loaded in footer so will still find tha elements
 */
	BetterLoader.init(doc.querySelectorAll('.bil-init'));
}(window, document);