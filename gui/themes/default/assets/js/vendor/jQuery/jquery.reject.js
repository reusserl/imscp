
/*!
 * jReject (jQuery Browser Rejection Plugin)
 * Version 1.1.x
 * URL: http://jreject.turnwheel.com/
 * Description: jReject is a easy method of rejecting specific browsers on your site
 * Author: Steven Bower (TurnWheel Designs) http://turnwheel.com/
 * Copyright: Copyright (c) 2009-2014 Steven Bower under dual MIT/GPLv2 license.
 */

(function($) {
	$.reject = function(options) {
		var opts = $.extend(true, {
			// Specifies which browsers/versions will be blocked
			reject : {
				/*
				 * Many possible combinations:
				 *
				 * You can specify rejection by browser nanmes (msie, chrome, firefox)
				 * You can specify rejection by engine name (gecko, trident)
				 * You can specify rejection by os (win, mac, linux, solaris, iphone, ipad)
				 *
				 * You can specify versions of each.
				 * Examples: msie9: true, firefox8: true,
				 *
				 * You can specify the highest number to reject.
				 * Example: msie: 9 (9 and lower are rejected).
				 *
				 * There is also "unknown" that covers what isn't detected
				 * Example: unknown: true
				 *
				 *
				 * Note: All property names must be lowercased.
				 */
			},
			display: [], // What browsers to display and their order (default set below)
			browserShow: true, // Should the browser options be shown?
			browserInfo: { // Settings for which browsers to display
				chrome: {
					// Text below the icon
					text: 'Google Chrome',
					// URL For icon/text link
					url: 'https://www.google.com/chrome/'
					// (Optional) Use "allow" to customized when to show this option
					// Example: to show chrome only for IE users
					// allow: { all: false, msie: true }
				},
				firefox: {
					text: 'Mozilla Firefox',
					url: 'https://www.mozilla.org/en-US/firefox/new/'
				},
				safari: {
					text: 'Safari',
					url: 'https://www.apple.com/safari/'
				},
				opera: {
					text: 'Opera',
					url: 'http://www.opera.com/computer/'
				},
				msie: {
					text: 'Internet Explorer',
					url: 'http://windows.microsoft.com/en-us/internet-explorer/download-ie'
				},
				konqueror: {
					text: 'Konqueror',
					url: 'https://konqueror.org/download'
				}
			},
			// Pop-up Window Text
			header: 'Your browser is not supported by i-MSCP',
			paragraph1: 'Your browser is out of date, and it is not supported by i-MSCP. Please install another ' +
			            'browser or update this one by clicking on the right icon below.',
			paragraph2: '',
			// Allow closing of window
			close: false,
			// Message displayed below closing link
			closeMessage: 'By closing this window you acknowledge that your experience with i-MSCP will be be degraded.',
			closeTitle: 'Close this Window',
			closeURL: '#',
			// Allows closing of window with esc key
			closeESC: true,
			// Use cookies to remmember if window was closed previously?
			closeCookie: true,
			// Cookie settings are only used if closeCookie is true
			cookieSettings: {
				// Path for the cookie to be saved on. Should be root domain in most cases
				path: '/',
				// Expiration Date (in seconds). 0 (default) means it ends with the current session
				expires: 0
			},
			// Path where images are located
			imagePath: '/assets/images/jReject/',
			// File extension of images
			imageFileExtension: 'gif',
			// Background color for overlay
			overlayBgColor: '#000',
			// Background transparency (0-1)
			overlayOpacity: 0.8,
			// Fade in time on open ('slow','medium','fast' or integer in ms)
			//fadeInTime: 'fast',
			// Fade out time on close ('slow','medium','fast' or integer in ms)
			fadeOutTime: 'fast'
		}, options);

		// Set default browsers to display if not already defined
		if (opts.display.length < 1) {
			opts.display = ['chrome', 'firefox', 'safari', 'opera', 'msie'];
		}

		// beforeRject: Customized Function
		if ($.isFunction(opts.beforeReject)) {
			opts.beforeReject();
		}

		// Disable 'closeESC' if closing is disabled (mutually exclusive)
		if (!opts.close) {
			opts.closeESC = false;
		}

		// This function parses the advanced browser options
		var browserCheck = function(settings) {
			// Check 1: Browser+major version (optional) (eg. 'firefox','msie','{msie: 6}')
			// Check 2: Browser+major version (eg. 'firefox3','msie7','chrome4')
			// Check 3: Engine+version (eg. 'webkit', 'gecko', '{webkit: 537.36}')
			// Check 4: Operating System (eg. 'win','mac','linux','solaris','iphone')
			var engine = settings[($.ua.engine.name || 'unknown').toLowerCase()];
			var browser = settings[($.ua.browser.name || 'unknown').toLowerCase()];

			return !!(
				(browser && (browser === true || parseFloat($.ua.browser.version) <= browser)) ||
				settings[($.ua.browser.name || 'unknown').toLowerCase() + $.ua.browser.major] ||
				(engine && (engine === true || parseFloat($.ua.engine.version) <= engine)) ||
				settings[($.ua.os.name || 'unknown').toLowerCase()]
			);
		};

		// Determine if we need to display rejection for this browser, or exit
		if (!browserCheck(opts.reject)) {
			// onFail: Optional Callback
			if ($.isFunction(opts.onFail)) {
				opts.onFail();
			}

			return false;
		}

		// If user can close and set to remmember close, initiate cookie functions
		if (opts.close && opts.closeCookie) {
			// Local global setting for the name of the cookie used
			var COOKIE_NAME = 'jreject-close';

			// Cookies Function: Handles creating/retrieving/deleting cookies
			// Cookies are only used for opts.closeCookie parameter functionality
			var _cookie = function(name, value) {
				// Save cookie
				if (typeof value != 'undefined') {
					var expires = '';

					// Check if we need to set an expiration date
					if (opts.cookieSettings.expires !== 0) {
						var date = new Date();
						date.setTime(date.getTime() + (opts.cookieSettings.expires * 1000));
						expires = "; expires=" + date.toGMTString();
					}

					// Get path from settings
					var path = opts.cookieSettings.path || '/';

					// Set Cookie with parameters
					document.cookie = name + '=' + encodeURIComponent((!value) ? '' : value) + expires + '; path=' + path;

					return true;
				} else { // Get cookie
					var cookie,val = null;

					if (document.cookie && document.cookie !== '') {
						var cookies = document.cookie.split(';');

						// Loop through all cookie values
						var clen = cookies.length;
						for (var i = 0; i < clen; ++i) {
							cookie = $.trim(cookies[i]);

							// Does this cookie string begin with the name we want?
							if (cookie.substring(0, name.length + 1) == (name + '=')) {
								var len = name.length;
								val = decodeURIComponent(cookie.substring(len + 1));
								break;
							}
						}
					}

					// Returns cookie value
					return val;
				}
			};

			// If cookie is set, return false and don't display rejection
			if (_cookie(COOKIE_NAME)) {
				return false;
			}
		}

		var html = '<div id="jr_overlay"></div><div id="jr_wrap"><div id="jr_inner" class="noselect">';

		// Display close button
		html += '<div id="jr_close">';
		if(opts.close) {
				html += '<a href="' + opts.closeURL + '">' +
					'<img src="' + opts.imagePath + 'close.' + opts.imageFileExtension + '" alt="' + opts.closeTitle + '" title="' + opts.closeTitle + '">' +
					'</a>';
		}
		html += '</div>';

		html += '<h1 id="jr_header">' + opts.header + '</h1>' +
			(opts.paragraph1 === '' ? '' : '<p>'+opts.paragraph1+'</p>') +
			(opts.paragraph2 === '' ? '' : '<p>'+opts.paragraph2+'</p>');

		var displayNum = 0;
		if (opts.browserShow) {
			html += '<ul>';

			// Generate the browsers to display
			for (var x in opts.display) {
				var browser = opts.display[x]; // Current Browser
				var info = opts.browserInfo[browser] || false; // Browser Information

				// If no info exists for this browser or if this browser is not suppose to display to this user
				// based on "allow" flag
				if (!info || (info['allow'] != undefined && !browserCheck(info['allow']))) {
					continue;
				}

				var url = info.url || '#'; // URL to link text/icon to

				// Generate HTML for this browser option
				html += '<li id="jr_' + browser + '"><div class="jr_icon"></div>' +
					'<div><a href="' + url + '">' + (info.text || 'Unknown') + '</a>' +
					'</div></li>';

				++displayNum;
			}

			html += '</ul>';
		}

		// Display close message
		html += '<div>' + (opts.close ? '<p>' + opts.closeMessage + '</p>' : '') + '</div>';
		html += '<div class="clear"></div>';
		html += '</div></div>';

		var element = $('<div>' + html + '</div>'); // Create element
		var size = _pageSize(); // Get page size
		var scroll = _scrollSize(); // Get page scroll

		// This function handles closing this reject window. When clicked, fadeOut and remove all elements
		element.bind('closejr', function() {
			// Make sure the permission to close is granted
			if (!opts.close) {
				return false;
			}

			// Customized Function
			if ($.isFunction(opts.beforeClose)) {
				opts.beforeClose();
			}

			// Remove binding function so it doesn't get called more than once
			$(this).unbind('closejr');

			// Fade out background and modal wrapper
			$('#jr_overlay,#jr_wrap').fadeOut(opts.fadeOutTime,function() {
				$(this).remove(); // Remove element from DOM

				// afterClose: Customized Function
				if ($.isFunction(opts.afterClose)) {
					opts.afterClose();
				}
			});

			// Show elements that were hidden for layering issues
			var elmhide = 'embed.jr_hidden, object.jr_hidden, select.jr_hidden, applet.jr_hidden';
			$(elmhide).show().removeClass('jr_hidden');

			// Set close cookie for next run
			if (opts.closeCookie) {
				_cookie(COOKIE_NAME, 'true');
			}

			return true;
		});

		// Called onClick for browser links (and icons). Opens link in new window
		var openBrowserLinks = function(url) {
			// Open window, generate random id value
			window.open(url, 'jr_' + Math.round(Math.random()*11));
			return false;
		};

		/*
		 * Trverse through element DOM and apply JS variables. All CSS elements that do not require JS will be in
		 * css/jquery.jreject.css
		 */

		// Creates 'background' (div)
		element.find('#jr_overlay').css({
			width: size[0],
			height: size[1],
			background: opts.overlayBgColor,
			opacity: opts.overlayOpacity
		});

		// Wrapper for our pop-up (div)
		element.find('#jr_wrap').css({
			top: scroll[1] + (size[3]/4),
			left: scroll[0]
		});

		// If browserShow is FALSE, use default CSS values for #jr_inner
		if(opts.browserShow) {
			// Wrapper for inner centered content (div)
			element.find('#jr_inner').css({
				minWidth: displayNum*100,
				maxWidth: displayNum*140,
				width: $.ua.engine.name == 'trident' ? displayNum * 155 : 'auto'
			});
		}

		element.find('#jr_inner li').css({ // Browser list items (li)
			background: 'transparent url("' + opts.imagePath + 'background_browser.' + opts.imageFileExtension + '") ' +
			'no-repeat scroll left top'
		});

		element.find('#jr_inner li .jr_icon').each(function() {
			// Dynamically sets the icon background image
			var self = $(this);
			self.css('background','transparent url(' + opts.imagePath + 'browser_' +
				(self.parent('li').attr('id').replace(/jr_/,''))+'.' + opts.imageFileExtension + ')' +
				' no-repeat scroll center center');

			// Send link clicks to openBrowserLinks
			self.click(function () {
				var url = $(this).next('div').children('a').attr('href');
				openBrowserLinks(url);
			});
		});

		element.find('#jr_inner li a').click(function() {
			openBrowserLinks($(this).attr('href'));
			return false;
		});

		// Bind closing event to trigger closejr to be consistant with ESC key close function
		element.find('#jr_close a').click(function() {
			$(this).trigger('closejr');

			// If plain anchor is set, return false so there is no page jump
			if (opts.closeURL === '#') {
				return false;
			}
		});

		// Set focus (fixes ESC key issues with forms and other focus bugs)
		$('#jr_overlay').focus();

		// Hide elements that won't display properly
		$('embed, object, select, applet').each(function() {
			if ($(this).is(':visible')) {
				$(this).hide().addClass('jr_hidden');
			}
		});

		// Append element to body of document to display
		//$('body').append(element.hide().fadeIn(opts.fadeInTime));
		$('body').append(element);

		// Handle window resize/scroll events and update overlay dimensions
		$(window).bind('resize scroll',function() {
			var size = _pageSize(); // Get size

			// Update overlay dimensions based on page size
			$('#jr_overlay').css({
				width: size[0],
				height: size[1]
			});

			var scroll = _scrollSize(); // Get page scroll

			// Update modal position based on scroll
			$('#jr_wrap').css({
				top: scroll[1] + (size[3]/4),
				left: scroll[0]
			});
		});

		// Add optional ESC Key functionality
		if (opts.closeESC) {
			$(document).bind('keydown',function(event) {
				// ESC = Keycode 27
				if (event.keyCode == 27) {
					element.trigger('closejr');
				}
			});
		}

		// afterReject: Customized Function
		if ($.isFunction(opts.afterReject)) {
			opts.afterReject();

		}

		return true;
	};

// Based on compatibility data from quirksmode.com
// This is used to help calculate exact center of the page
	var _pageSize = function() {
		var xScroll = window.innerWidth && window.scrollMaxX ?
		window.innerWidth + window.scrollMaxX :
			(document.body.scrollWidth > document.body.offsetWidth ?
				document.body.scrollWidth : document.body.offsetWidth);

		var yScroll = window.innerHeight && window.scrollMaxY ?
		window.innerHeight + window.scrollMaxY :
			(document.body.scrollHeight > document.body.offsetHeight ?
				document.body.scrollHeight : document.body.offsetHeight);

		var windowWidth = window.innerWidth ? window.innerWidth :
			(document.documentElement && document.documentElement.clientWidth ?
				document.documentElement.clientWidth : document.body.clientWidth);

		var windowHeight = window.innerHeight ? window.innerHeight :
			(document.documentElement && document.documentElement.clientHeight ?
				document.documentElement.clientHeight : document.body.clientHeight);

		return [
			xScroll < windowWidth ? xScroll : windowWidth, // Page Width
			yScroll < windowHeight ? windowHeight : yScroll, // Page Height
			windowWidth,windowHeight
		];
	};


// Based on compatibility data from quirksmode.com
	var _scrollSize = function() {
		return [
			// scrollSize X
			window.pageXOffset ? window.pageXOffset : (document.documentElement &&
			document.documentElement.scrollTop ?
				document.documentElement.scrollLeft : document.body.scrollLeft),

			// scrollSize Y
			window.pageYOffset ? window.pageYOffset : (document.documentElement &&
			document.documentElement.scrollTop ?
				document.documentElement.scrollTop : document.body.scrollTop)
		];
	};
})(jQuery);

/**
 * UAParser.js v0.7.9
 * Lightweight JavaScript-based User-Agent string parser
 * https://github.com/faisalman/ua-parser-js
 *
 * Copyright © 2012-2015 Faisal Salman <fyzlman@gmail.com>
 * Dual licensed under GPLv2 & MIT
 */

(function (window, undefined) {

	'use strict';

	//////////////
	// Constants
	/////////////

	var LIBVERSION  = '0.7.9',
		EMPTY       = '',
		UNKNOWN     = '?',
		FUNC_TYPE   = 'function',
		UNDEF_TYPE  = 'undefined',
		OBJ_TYPE    = 'object',
		STR_TYPE    = 'string',
		MAJOR       = 'major', // deprecated
		MODEL       = 'model',
		NAME        = 'name',
		TYPE        = 'type',
		VENDOR      = 'vendor',
		VERSION     = 'version',
		ARCHITECTURE= 'architecture',
		CONSOLE     = 'console',
		MOBILE      = 'mobile',
		TABLET      = 'tablet',
		SMARTTV     = 'smarttv',
		WEARABLE    = 'wearable',
		EMBEDDED    = 'embedded';

	///////////
	// Helper
	//////////

	var util = {
		extend : function (regexes, extensions) {
			for (var i in extensions) {
				if ("browser cpu device engine os".indexOf(i) !== -1 && extensions[i].length % 2 === 0) {
					regexes[i] = extensions[i].concat(regexes[i]);
				}
			}
			return regexes;
		},
		has : function (str1, str2) {
			if (typeof str1 === "string") {
				return str2.toLowerCase().indexOf(str1.toLowerCase()) !== -1;
			} else {
				return false;
			}
		},
		lowerize : function (str) {
			return str.toLowerCase();
		},
		major : function (version) {
			return typeof(version) === STR_TYPE ? version.split(".")[0] : undefined;
		}
	};

	///////////////
	// Map helper
	//////////////

	var mapper = {

		rgx : function () {
			var result, i = 0, j, k, p, q, matches, match, args = arguments;

			// loop through all regexes maps
			while (i < args.length && !matches) {

				var regex = args[i],       // even sequence (0,2,4,..)
					props = args[i + 1];   // odd sequence (1,3,5,..)

				// construct object barebones
				if (typeof result === UNDEF_TYPE) {
					result = {};
					for (p in props) {
						if (props.hasOwnProperty(p)){
							q = props[p];
							if (typeof q === OBJ_TYPE) {
								result[q[0]] = undefined;
							} else {
								result[q] = undefined;
							}
						}
					}
				}

				// try matching uastring with regexes
				j = k = 0;
				while (j < regex.length && !matches) {
					matches = regex[j++].exec(this.getUA());
					if (!!matches) {
						for (p = 0; p < props.length; p++) {
							match = matches[++k];
							q = props[p];
							// check if given property is actually array
							if (typeof q === OBJ_TYPE && q.length > 0) {
								if (q.length == 2) {
									if (typeof q[1] == FUNC_TYPE) {
										// assign modified match
										result[q[0]] = q[1].call(this, match);
									} else {
										// assign given value, ignore regex match
										result[q[0]] = q[1];
									}
								} else if (q.length == 3) {
									// check whether function or regex
									if (typeof q[1] === FUNC_TYPE && !(q[1].exec && q[1].test)) {
										// call function (usually string mapper)
										result[q[0]] = match ? q[1].call(this, match, q[2]) : undefined;
									} else {
										// sanitize match using given regex
										result[q[0]] = match ? match.replace(q[1], q[2]) : undefined;
									}
								} else if (q.length == 4) {
									result[q[0]] = match ? q[3].call(this, match.replace(q[1], q[2])) : undefined;
								}
							} else {
								result[q] = match ? match : undefined;
							}
						}
					}
				}
				i += 2;
			}
			return result;
		},

		str : function (str, map) {

			for (var i in map) {
				// check if array
				if (typeof map[i] === OBJ_TYPE && map[i].length > 0) {
					for (var j = 0; j < map[i].length; j++) {
						if (util.has(map[i][j], str)) {
							return (i === UNKNOWN) ? undefined : i;
						}
					}
				} else if (util.has(map[i], str)) {
					return (i === UNKNOWN) ? undefined : i;
				}
			}
			return str;
		}
	};

	///////////////
	// String map
	//////////////

	var maps = {

		browser : {
			oldsafari : {
				version : {
					'1.0'   : '/8',
					'1.2'   : '/1',
					'1.3'   : '/3',
					'2.0'   : '/412',
					'2.0.2' : '/416',
					'2.0.3' : '/417',
					'2.0.4' : '/419',
					'?'     : '/'
				}
			}
		},

		device : {
			amazon : {
				model : {
					'Fire Phone' : ['SD', 'KF']
				}
			},
			sprint : {
				model : {
					'Evo Shift 4G' : '7373KT'
				},
				vendor : {
					'HTC'       : 'APA',
					'Sprint'    : 'Sprint'
				}
			}
		},

		os : {
			windows : {
				version : {
					'ME'        : '4.90',
					'NT 3.11'   : 'NT3.51',
					'NT 4.0'    : 'NT4.0',
					'2000'      : 'NT 5.0',
					'XP'        : ['NT 5.1', 'NT 5.2'],
					'Vista'     : 'NT 6.0',
					'7'         : 'NT 6.1',
					'8'         : 'NT 6.2',
					'8.1'       : 'NT 6.3',
					'10'        : ['NT 6.4', 'NT 10.0'],
					'RT'        : 'ARM'
				}
			}
		}
	};

	//////////////
	// Regex map
	/////////////

	var regexes = {

		browser : [[

			// Presto based
			/(opera\smini)\/([\w\.-]+)/i,                                       // Opera Mini
			/(opera\s[mobiletab]+).+version\/([\w\.-]+)/i,                      // Opera Mobi/Tablet
			/(opera).+version\/([\w\.]+)/i,                                     // Opera > 9.80
			/(opera)[\/\s]+([\w\.]+)/i                                          // Opera < 9.80

		], [NAME, VERSION], [

			/\s(opr)\/([\w\.]+)/i                                               // Opera Webkit
		], [[NAME, 'Opera'], VERSION], [

			// Mixed
			/(kindle)\/([\w\.]+)/i,                                             // Kindle
			/(lunascape|maxthon|netfront|jasmine|blazer)[\/\s]?([\w\.]+)*/i,
			// Lunascape/Maxthon/Netfront/Jasmine/Blazer

			// Trident based
			/(avant\s|iemobile|slim|baidu)(?:browser)?[\/\s]?([\w\.]*)/i,
			// Avant/IEMobile/SlimBrowser/Baidu
			/(?:ms|\()(ie)\s([\w\.]+)/i,                                        // Internet Explorer

			// Webkit/KHTML based
			/(rekonq)\/([\w\.]+)*/i,                                            // Rekonq
			/(chromium|flock|rockmelt|midori|epiphany|silk|skyfire|ovibrowser|bolt|iron|vivaldi|iridium)\/([\w\.-]+)/i
			// Chromium/Flock/RockMelt/Midori/Epiphany/Silk/Skyfire/Bolt/Iron/Iridium
		], [NAME, VERSION], [

			/(trident).+rv[:\s]([\w\.]+).+like\sgecko/i                         // IE11
		], [[NAME, 'IE'], VERSION], [

			/(edge)\/((\d+)?[\w\.]+)/i                                          // Microsoft Edge
		], [NAME, VERSION], [

			/(yabrowser)\/([\w\.]+)/i                                           // Yandex
		], [[NAME, 'Yandex'], VERSION], [

			/(comodo_dragon)\/([\w\.]+)/i                                       // Comodo Dragon
		], [[NAME, /_/g, ' '], VERSION], [

			/(chrome|omniweb|arora|[tizenoka]{5}\s?browser)\/v?([\w\.]+)/i,
			// Chrome/OmniWeb/Arora/Tizen/Nokia
			/(qqbrowser)[\/\s]?([\w\.]+)/i
			// QQBrowser
		], [NAME, VERSION], [

			/(uc\s?browser)[\/\s]?([\w\.]+)/i,
			/ucweb.+(ucbrowser)[\/\s]?([\w\.]+)/i,
			/JUC.+(ucweb)[\/\s]?([\w\.]+)/i
			// UCBrowser
		], [[NAME, 'UCBrowser'], VERSION], [

			/(dolfin)\/([\w\.]+)/i                                              // Dolphin
		], [[NAME, 'Dolphin'], VERSION], [

			/((?:android.+)crmo|crios)\/([\w\.]+)/i                             // Chrome for Android/iOS
		], [[NAME, 'Chrome'], VERSION], [

			/XiaoMi\/MiuiBrowser\/([\w\.]+)/i                                   // MIUI Browser
		], [VERSION, [NAME, 'MIUI Browser']], [

			/android.+version\/([\w\.]+)\s+(?:mobile\s?safari|safari)/i         // Android Browser
		], [VERSION, [NAME, 'Android Browser']], [

			/FBAV\/([\w\.]+);/i                                                 // Facebook App for iOS
		], [VERSION, [NAME, 'Facebook']], [

			/version\/([\w\.]+).+?mobile\/\w+\s(safari)/i                       // Mobile Safari
		], [VERSION, [NAME, 'Mobile Safari']], [

			/version\/([\w\.]+).+?(mobile\s?safari|safari)/i                    // Safari & Safari Mobile
		], [VERSION, NAME], [

			/webkit.+?(mobile\s?safari|safari)(\/[\w\.]+)/i                     // Safari < 3.0
		], [NAME, [VERSION, mapper.str, maps.browser.oldsafari.version]], [

			/(konqueror)\/([\w\.]+)/i,                                          // Konqueror
			/(webkit|khtml)\/([\w\.]+)/i
		], [NAME, VERSION], [

			// Gecko based
			/(navigator|netscape)\/([\w\.-]+)/i                                 // Netscape
		], [[NAME, 'Netscape'], VERSION], [
			/fxios\/([\w\.-]+)/i                                                // Firefox for iOS
		], [VERSION, [NAME, 'Firefox']], [
			/(swiftfox)/i,                                                      // Swiftfox
			/(icedragon|iceweasel|camino|chimera|fennec|maemo\sbrowser|minimo|conkeror)[\/\s]?([\w\.\+]+)/i,
			// IceDragon/Iceweasel/Camino/Chimera/Fennec/Maemo/Minimo/Conkeror
			/(firefox|seamonkey|k-meleon|icecat|iceape|firebird|phoenix)\/([\w\.-]+)/i,
			// Firefox/SeaMonkey/K-Meleon/IceCat/IceApe/Firebird/Phoenix
			/(mozilla)\/([\w\.]+).+rv\:.+gecko\/\d+/i,                          // Mozilla

			// Other
			/(polaris|lynx|dillo|icab|doris|amaya|w3m|netsurf)[\/\s]?([\w\.]+)/i,
			// Polaris/Lynx/Dillo/iCab/Doris/Amaya/w3m/NetSurf
			/(links)\s\(([\w\.]+)/i,                                            // Links
			/(gobrowser)\/?([\w\.]+)*/i,                                        // GoBrowser
			/(ice\s?browser)\/v?([\w\._]+)/i,                                   // ICE Browser
			/(mosaic)[\/\s]([\w\.]+)/i                                          // Mosaic
		], [NAME, VERSION]

			/* /////////////////////
			 // Media players BEGIN
			 ////////////////////////

			 , [

			 /(apple(?:coremedia|))\/((\d+)[\w\._]+)/i,                          // Generic Apple CoreMedia
			 /(coremedia) v((\d+)[\w\._]+)/i
			 ], [NAME, VERSION], [

			 /(aqualung|lyssna|bsplayer)\/((\d+)?[\w\.-]+)/i                     // Aqualung/Lyssna/BSPlayer
			 ], [NAME, VERSION], [

			 /(ares|ossproxy)\s((\d+)[\w\.-]+)/i                                 // Ares/OSSProxy
			 ], [NAME, VERSION], [

			 /(audacious|audimusicstream|amarok|bass|core|dalvik|gnomemplayer|music on console|nsplayer|psp-internetradioplayer|videos)\/((\d+)[\w\.-]+)/i,
			 // Audacious/AudiMusicStream/Amarok/BASS/OpenCORE/Dalvik/GnomeMplayer/MoC
			 // NSPlayer/PSP-InternetRadioPlayer/Videos
			 /(clementine|music player daemon)\s((\d+)[\w\.-]+)/i,               // Clementine/MPD
			 /(lg player|nexplayer)\s((\d+)[\d\.]+)/i,
			 /player\/(nexplayer|lg player)\s((\d+)[\w\.-]+)/i                   // NexPlayer/LG Player
			 ], [NAME, VERSION], [
			 /(nexplayer)\s((\d+)[\w\.-]+)/i                                     // Nexplayer
			 ], [NAME, VERSION], [

			 /(flrp)\/((\d+)[\w\.-]+)/i                                          // Flip Player
			 ], [[NAME, 'Flip Player'], VERSION], [

			 /(fstream|nativehost|queryseekspider|ia-archiver|facebookexternalhit)/i
			 // FStream/NativeHost/QuerySeekSpider/IA Archiver/facebookexternalhit
			 ], [NAME], [

			 /(gstreamer) souphttpsrc (?:\([^\)]+\)){0,1} libsoup\/((\d+)[\w\.-]+)/i
			 // Gstreamer
			 ], [NAME, VERSION], [

			 /(htc streaming player)\s[\w_]+\s\/\s((\d+)[\d\.]+)/i,              // HTC Streaming Player
			 /(java|python-urllib|python-requests|wget|libcurl)\/((\d+)[\w\.-_]+)/i,
			 // Java/urllib/requests/wget/cURL
			 /(lavf)((\d+)[\d\.]+)/i                                             // Lavf (FFMPEG)
			 ], [NAME, VERSION], [

			 /(htc_one_s)\/((\d+)[\d\.]+)/i                                      // HTC One S
			 ], [[NAME, /_/g, ' '], VERSION], [

			 /(mplayer)(?:\s|\/)(?:(?:sherpya-){0,1}svn)(?:-|\s)(r\d+(?:-\d+[\w\.-]+){0,1})/i
			 // MPlayer SVN
			 ], [NAME, VERSION], [

			 /(mplayer)(?:\s|\/|[unkow-]+)((\d+)[\w\.-]+)/i                      // MPlayer
			 ], [NAME, VERSION], [

			 /(mplayer)/i,                                                       // MPlayer (no other info)
			 /(yourmuze)/i,                                                      // YourMuze
			 /(media player classic|nero showtime)/i                             // Media Player Classic/Nero ShowTime
			 ], [NAME], [

			 /(nero (?:home|scout))\/((\d+)[\w\.-]+)/i                           // Nero Home/Nero Scout
			 ], [NAME, VERSION], [

			 /(nokia\d+)\/((\d+)[\w\.-]+)/i                                      // Nokia
			 ], [NAME, VERSION], [

			 /\s(songbird)\/((\d+)[\w\.-]+)/i                                    // Songbird/Philips-Songbird
			 ], [NAME, VERSION], [

			 /(winamp)3 version ((\d+)[\w\.-]+)/i,                               // Winamp
			 /(winamp)\s((\d+)[\w\.-]+)/i,
			 /(winamp)mpeg\/((\d+)[\w\.-]+)/i
			 ], [NAME, VERSION], [

			 /(ocms-bot|tapinradio|tunein radio|unknown|winamp|inlight radio)/i  // OCMS-bot/tap in radio/tunein/unknown/winamp (no other info)
			 // inlight radio
			 ], [NAME], [

			 /(quicktime|rma|radioapp|radioclientapplication|soundtap|totem|stagefright|streamium)\/((\d+)[\w\.-]+)/i
			 // QuickTime/RealMedia/RadioApp/RadioClientApplication/
			 // SoundTap/Totem/Stagefright/Streamium
			 ], [NAME, VERSION], [

			 /(smp)((\d+)[\d\.]+)/i                                              // SMP
			 ], [NAME, VERSION], [

			 /(vlc) media player - version ((\d+)[\w\.]+)/i,                     // VLC Videolan
			 /(vlc)\/((\d+)[\w\.-]+)/i,
			 /(xbmc|gvfs|xine|xmms|irapp)\/((\d+)[\w\.-]+)/i,                    // XBMC/gvfs/Xine/XMMS/irapp
			 /(foobar2000)\/((\d+)[\d\.]+)/i,                                    // Foobar2000
			 /(itunes)\/((\d+)[\d\.]+)/i                                         // iTunes
			 ], [NAME, VERSION], [

			 /(wmplayer)\/((\d+)[\w\.-]+)/i,                                     // Windows Media Player
			 /(windows-media-player)\/((\d+)[\w\.-]+)/i
			 ], [[NAME, /-/g, ' '], VERSION], [

			 /windows\/((\d+)[\w\.-]+) upnp\/[\d\.]+ dlnadoc\/[\d\.]+ (home media server)/i
			 // Windows Media Server
			 ], [VERSION, [NAME, 'Windows']], [

			 /(com\.riseupradioalarm)\/((\d+)[\d\.]*)/i                          // RiseUP Radio Alarm
			 ], [NAME, VERSION], [

			 /(rad.io)\s((\d+)[\d\.]+)/i,                                        // Rad.io
			 /(radio.(?:de|at|fr))\s((\d+)[\d\.]+)/i
			 ], [[NAME, 'rad.io'], VERSION]

			 //////////////////////
			 // Media players END
			 ////////////////////*/

		],

		cpu : [[

			/(?:(amd|x(?:(?:86|64)[_-])?|wow|win)64)[;\)]/i                     // AMD64
		], [[ARCHITECTURE, 'amd64']], [

			/(ia32(?=;))/i                                                      // IA32 (quicktime)
		], [[ARCHITECTURE, util.lowerize]], [

			/((?:i[346]|x)86)[;\)]/i                                            // IA32
		], [[ARCHITECTURE, 'ia32']], [

			// PocketPC mistakenly identified as PowerPC
			/windows\s(ce|mobile);\sppc;/i
		], [[ARCHITECTURE, 'arm']], [

			/((?:ppc|powerpc)(?:64)?)(?:\smac|;|\))/i                           // PowerPC
		], [[ARCHITECTURE, /ower/, '', util.lowerize]], [

			/(sun4\w)[;\)]/i                                                    // SPARC
		], [[ARCHITECTURE, 'sparc']], [

			/((?:avr32|ia64(?=;))|68k(?=\))|arm(?:64|(?=v\d+;))|(?=atmel\s)avr|(?:irix|mips|sparc)(?:64)?(?=;)|pa-risc)/i
			// IA64, 68K, ARM/64, AVR/32, IRIX/64, MIPS/64, SPARC/64, PA-RISC
		], [[ARCHITECTURE, util.lowerize]]
		],

		device : [[

			/\((ipad|playbook);[\w\s\);-]+(rim|apple)/i                         // iPad/PlayBook
		], [MODEL, VENDOR, [TYPE, TABLET]], [

			/applecoremedia\/[\w\.]+ \((ipad)/                                  // iPad
		], [MODEL, [VENDOR, 'Apple'], [TYPE, TABLET]], [

			/(apple\s{0,1}tv)/i                                                 // Apple TV
		], [[MODEL, 'Apple TV'], [VENDOR, 'Apple']], [

			/(archos)\s(gamepad2?)/i,                                           // Archos
			/(hp).+(touchpad)/i,                                                // HP TouchPad
			/(kindle)\/([\w\.]+)/i,                                             // Kindle
			/\s(nook)[\w\s]+build\/(\w+)/i,                                     // Nook
			/(dell)\s(strea[kpr\s\d]*[\dko])/i                                  // Dell Streak
		], [VENDOR, MODEL, [TYPE, TABLET]], [

			/(kf[A-z]+)\sbuild\/[\w\.]+.*silk\//i                               // Kindle Fire HD
		], [MODEL, [VENDOR, 'Amazon'], [TYPE, TABLET]], [
			/(sd|kf)[0349hijorstuw]+\sbuild\/[\w\.]+.*silk\//i                  // Fire Phone
		], [[MODEL, mapper.str, maps.device.amazon.model], [VENDOR, 'Amazon'], [TYPE, MOBILE]], [

			/\((ip[honed|\s\w*]+);.+(apple)/i                                   // iPod/iPhone
		], [MODEL, VENDOR, [TYPE, MOBILE]], [
			/\((ip[honed|\s\w*]+);/i                                            // iPod/iPhone
		], [MODEL, [VENDOR, 'Apple'], [TYPE, MOBILE]], [

			/(blackberry)[\s-]?(\w+)/i,                                         // BlackBerry
			/(blackberry|benq|palm(?=\-)|sonyericsson|acer|asus|dell|huawei|meizu|motorola|polytron)[\s_-]?([\w-]+)*/i,
			// BenQ/Palm/Sony-Ericsson/Acer/Asus/Dell/Huawei/Meizu/Motorola/Polytron
			/(hp)\s([\w\s]+\w)/i,                                               // HP iPAQ
			/(asus)-?(\w+)/i                                                    // Asus
		], [VENDOR, MODEL, [TYPE, MOBILE]], [
			/\(bb10;\s(\w+)/i                                                   // BlackBerry 10
		], [MODEL, [VENDOR, 'BlackBerry'], [TYPE, MOBILE]], [
			// Asus Tablets
			/android.+(transfo[prime\s]{4,10}\s\w+|eeepc|slider\s\w+|nexus 7)/i
		], [MODEL, [VENDOR, 'Asus'], [TYPE, TABLET]], [

			/(sony)\s(tablet\s[ps])\sbuild\//i,                                  // Sony
			/(sony)?(?:sgp.+)\sbuild\//i
		], [[VENDOR, 'Sony'], [MODEL, 'Xperia Tablet'], [TYPE, TABLET]], [
			/(?:sony)?(?:(?:(?:c|d)\d{4})|(?:so[-l].+))\sbuild\//i
		], [[VENDOR, 'Sony'], [MODEL, 'Xperia Phone'], [TYPE, MOBILE]], [

			/\s(ouya)\s/i,                                                      // Ouya
			/(nintendo)\s([wids3u]+)/i                                          // Nintendo
		], [VENDOR, MODEL, [TYPE, CONSOLE]], [

			/android.+;\s(shield)\sbuild/i                                      // Nvidia
		], [MODEL, [VENDOR, 'Nvidia'], [TYPE, CONSOLE]], [

			/(playstation\s[34portablevi]+)/i                                   // Playstation
		], [MODEL, [VENDOR, 'Sony'], [TYPE, CONSOLE]], [

			/(sprint\s(\w+))/i                                                  // Sprint Phones
		], [[VENDOR, mapper.str, maps.device.sprint.vendor], [MODEL, mapper.str, maps.device.sprint.model], [TYPE, MOBILE]], [

			/(lenovo)\s?(S(?:5000|6000)+(?:[-][\w+]))/i                         // Lenovo tablets
		], [VENDOR, MODEL, [TYPE, TABLET]], [

			/(htc)[;_\s-]+([\w\s]+(?=\))|\w+)*/i,                               // HTC
			/(zte)-(\w+)*/i,                                                    // ZTE
			/(alcatel|geeksphone|huawei|lenovo|nexian|panasonic|(?=;\s)sony)[_\s-]?([\w-]+)*/i
			// Alcatel/GeeksPhone/Huawei/Lenovo/Nexian/Panasonic/Sony
		], [VENDOR, [MODEL, /_/g, ' '], [TYPE, MOBILE]], [

			/(nexus\s9)/i                                                       // HTC Nexus 9
		], [MODEL, [VENDOR, 'HTC'], [TYPE, TABLET]], [

			/[\s\(;](xbox(?:\sone)?)[\s\);]/i                                   // Microsoft Xbox
		], [MODEL, [VENDOR, 'Microsoft'], [TYPE, CONSOLE]], [
			/(kin\.[onetw]{3})/i                                                // Microsoft Kin
		], [[MODEL, /\./g, ' '], [VENDOR, 'Microsoft'], [TYPE, MOBILE]], [

			// Motorola
			/\s(milestone|droid(?:[2-4x]|\s(?:bionic|x2|pro|razr))?(:?\s4g)?)[\w\s]+build\//i,
			/mot[\s-]?(\w+)*/i,
			/(XT\d{3,4}) build\//i,
			/(nexus\s[6])/i
		], [MODEL, [VENDOR, 'Motorola'], [TYPE, MOBILE]], [
			/android.+\s(mz60\d|xoom[\s2]{0,2})\sbuild\//i
		], [MODEL, [VENDOR, 'Motorola'], [TYPE, TABLET]], [

			/android.+((sch-i[89]0\d|shw-m380s|gt-p\d{4}|gt-n8000|sgh-t8[56]9|nexus 10))/i,
			/((SM-T\w+))/i
		], [[VENDOR, 'Samsung'], MODEL, [TYPE, TABLET]], [                  // Samsung
			/((s[cgp]h-\w+|gt-\w+|galaxy\snexus|sm-n900))/i,
			/(sam[sung]*)[\s-]*(\w+-?[\w-]*)*/i,
			/sec-((sgh\w+))/i
		], [[VENDOR, 'Samsung'], MODEL, [TYPE, MOBILE]], [
			/(samsung);smarttv/i
		], [VENDOR, MODEL, [TYPE, SMARTTV]], [

			/\(dtv[\);].+(aquos)/i                                              // Sharp
		], [MODEL, [VENDOR, 'Sharp'], [TYPE, SMARTTV]], [
			/sie-(\w+)*/i                                                       // Siemens
		], [MODEL, [VENDOR, 'Siemens'], [TYPE, MOBILE]], [

			/(maemo|nokia).*(n900|lumia\s\d+)/i,                                // Nokia
			/(nokia)[\s_-]?([\w-]+)*/i
		], [[VENDOR, 'Nokia'], MODEL, [TYPE, MOBILE]], [

			/android\s3\.[\s\w;-]{10}(a\d{3})/i                                 // Acer
		], [MODEL, [VENDOR, 'Acer'], [TYPE, TABLET]], [

			/android\s3\.[\s\w;-]{10}(lg?)-([06cv9]{3,4})/i                     // LG Tablet
		], [[VENDOR, 'LG'], MODEL, [TYPE, TABLET]], [
			/(lg) netcast\.tv/i                                                 // LG SmartTV
		], [VENDOR, MODEL, [TYPE, SMARTTV]], [
			/(nexus\s[45])/i,                                                   // LG
			/lg[e;\s\/-]+(\w+)*/i
		], [MODEL, [VENDOR, 'LG'], [TYPE, MOBILE]], [

			/android.+(ideatab[a-z0-9\-\s]+)/i                                  // Lenovo
		], [MODEL, [VENDOR, 'Lenovo'], [TYPE, TABLET]], [

			/linux;.+((jolla));/i                                               // Jolla
		], [VENDOR, MODEL, [TYPE, MOBILE]], [

			/((pebble))app\/[\d\.]+\s/i                                         // Pebble
		], [VENDOR, MODEL, [TYPE, WEARABLE]], [

			/android.+;\s(glass)\s\d/i                                          // Google Glass
		], [MODEL, [VENDOR, 'Google'], [TYPE, WEARABLE]], [

			/android.+(\w+)\s+build\/hm\1/i,                                        // Xiaomi Hongmi 'numeric' models
			/android.+(hm[\s\-_]*note?[\s_]*(?:\d\w)?)\s+build/i,                   // Xiaomi Hongmi
			/android.+(mi[\s\-_]*(?:one|one[\s_]plus)?[\s_]*(?:\d\w)?)\s+build/i    // Xiaomi Mi
		], [[MODEL, /_/g, ' '], [VENDOR, 'Xiaomi'], [TYPE, MOBILE]], [

			/(mobile|tablet);.+rv\:.+gecko\//i                                  // Unidentifiable
		], [[TYPE, util.lowerize], VENDOR, MODEL]

			/*//////////////////////////
			 // TODO: move to string map
			 ////////////////////////////

			 /(C6603)/i                                                          // Sony Xperia Z C6603
			 ], [[MODEL, 'Xperia Z C6603'], [VENDOR, 'Sony'], [TYPE, MOBILE]], [
			 /(C6903)/i                                                          // Sony Xperia Z 1
			 ], [[MODEL, 'Xperia Z 1'], [VENDOR, 'Sony'], [TYPE, MOBILE]], [

			 /(SM-G900[F|H])/i                                                   // Samsung Galaxy S5
			 ], [[MODEL, 'Galaxy S5'], [VENDOR, 'Samsung'], [TYPE, MOBILE]], [
			 /(SM-G7102)/i                                                       // Samsung Galaxy Grand 2
			 ], [[MODEL, 'Galaxy Grand 2'], [VENDOR, 'Samsung'], [TYPE, MOBILE]], [
			 /(SM-G530H)/i                                                       // Samsung Galaxy Grand Prime
			 ], [[MODEL, 'Galaxy Grand Prime'], [VENDOR, 'Samsung'], [TYPE, MOBILE]], [
			 /(SM-G313HZ)/i                                                      // Samsung Galaxy V
			 ], [[MODEL, 'Galaxy V'], [VENDOR, 'Samsung'], [TYPE, MOBILE]], [
			 /(SM-T805)/i                                                        // Samsung Galaxy Tab S 10.5
			 ], [[MODEL, 'Galaxy Tab S 10.5'], [VENDOR, 'Samsung'], [TYPE, TABLET]], [
			 /(SM-G800F)/i                                                       // Samsung Galaxy S5 Mini
			 ], [[MODEL, 'Galaxy S5 Mini'], [VENDOR, 'Samsung'], [TYPE, MOBILE]], [
			 /(SM-T311)/i                                                        // Samsung Galaxy Tab 3 8.0
			 ], [[MODEL, 'Galaxy Tab 3 8.0'], [VENDOR, 'Samsung'], [TYPE, TABLET]], [

			 /(R1001)/i                                                          // Oppo R1001
			 ], [MODEL, [VENDOR, 'OPPO'], [TYPE, MOBILE]], [
			 /(X9006)/i                                                          // Oppo Find 7a
			 ], [[MODEL, 'Find 7a'], [VENDOR, 'Oppo'], [TYPE, MOBILE]], [
			 /(R2001)/i                                                          // Oppo YOYO R2001
			 ], [[MODEL, 'Yoyo R2001'], [VENDOR, 'Oppo'], [TYPE, MOBILE]], [
			 /(R815)/i                                                           // Oppo Clover R815
			 ], [[MODEL, 'Clover R815'], [VENDOR, 'Oppo'], [TYPE, MOBILE]], [
			 /(U707)/i                                                          // Oppo Find Way S
			 ], [[MODEL, 'Find Way S'], [VENDOR, 'Oppo'], [TYPE, MOBILE]], [

			 /(T3C)/i                                                            // Advan Vandroid T3C
			 ], [MODEL, [VENDOR, 'Advan'], [TYPE, TABLET]], [
			 /(ADVAN T1J\+)/i                                                    // Advan Vandroid T1J+
			 ], [[MODEL, 'Vandroid T1J+'], [VENDOR, 'Advan'], [TYPE, TABLET]], [
			 /(ADVAN S4A)/i                                                      // Advan Vandroid S4A
			 ], [[MODEL, 'Vandroid S4A'], [VENDOR, 'Advan'], [TYPE, MOBILE]], [

			 /(V972M)/i                                                          // ZTE V972M
			 ], [MODEL, [VENDOR, 'ZTE'], [TYPE, MOBILE]], [

			 /(i-mobile)\s(IQ\s[\d\.]+)/i                                        // i-mobile IQ
			 ], [VENDOR, MODEL, [TYPE, MOBILE]], [
			 /(IQ6.3)/i                                                          // i-mobile IQ IQ 6.3
			 ], [[MODEL, 'IQ 6.3'], [VENDOR, 'i-mobile'], [TYPE, MOBILE]], [
			 /(i-mobile)\s(i-style\s[\d\.]+)/i                                   // i-mobile i-STYLE
			 ], [VENDOR, MODEL, [TYPE, MOBILE]], [
			 /(i-STYLE2.1)/i                                                     // i-mobile i-STYLE 2.1
			 ], [[MODEL, 'i-STYLE 2.1'], [VENDOR, 'i-mobile'], [TYPE, MOBILE]], [

			 /(mobiistar touch LAI 512)/i                                        // mobiistar touch LAI 512
			 ], [[MODEL, 'Touch LAI 512'], [VENDOR, 'mobiistar'], [TYPE, MOBILE]], [

			 /////////////
			 // END TODO
			 ///////////*/

		],

		engine : [[

			/windows.+\sedge\/([\w\.]+)/i                                       // EdgeHTML
		], [VERSION, [NAME, 'EdgeHTML']], [

			/(presto)\/([\w\.]+)/i,                                             // Presto
			/(webkit|trident|netfront|netsurf|amaya|lynx|w3m)\/([\w\.]+)/i,     // WebKit/Trident/NetFront/NetSurf/Amaya/Lynx/w3m
			/(khtml|tasman|links)[\/\s]\(?([\w\.]+)/i,                          // KHTML/Tasman/Links
			/(icab)[\/\s]([23]\.[\d\.]+)/i                                      // iCab
		], [NAME, VERSION], [

			/rv\:([\w\.]+).*(gecko)/i                                           // Gecko
		], [VERSION, NAME]
		],

		os : [[

			// Windows based
			/microsoft\s(windows)\s(vista|xp)/i                                 // Windows (iTunes)
		], [NAME, VERSION], [
			/(windows)\snt\s6\.2;\s(arm)/i,                                     // Windows RT
			/(windows\sphone(?:\sos)*|windows\smobile|windows)[\s\/]?([ntce\d\.\s]+\w)/i
		], [NAME, [VERSION, mapper.str, maps.os.windows.version]], [
			/(win(?=3|9|n)|win\s9x\s)([nt\d\.]+)/i
		], [[NAME, 'Windows'], [VERSION, mapper.str, maps.os.windows.version]], [

			// Mobile/Embedded OS
			/\((bb)(10);/i                                                      // BlackBerry 10
		], [[NAME, 'BlackBerry'], VERSION], [
			/(blackberry)\w*\/?([\w\.]+)*/i,                                    // Blackberry
			/(tizen)[\/\s]([\w\.]+)/i,                                          // Tizen
			/(android|webos|palm\sos|qnx|bada|rim\stablet\sos|meego|contiki)[\/\s-]?([\w\.]+)*/i,
			// Android/WebOS/Palm/QNX/Bada/RIM/MeeGo/Contiki
			/linux;.+(sailfish);/i                                              // Sailfish OS
		], [NAME, VERSION], [
			/(symbian\s?os|symbos|s60(?=;))[\/\s-]?([\w\.]+)*/i                 // Symbian
		], [[NAME, 'Symbian'], VERSION], [
			/\((series40);/i                                                    // Series 40
		], [NAME], [
			/mozilla.+\(mobile;.+gecko.+firefox/i                               // Firefox OS
		], [[NAME, 'Firefox OS'], VERSION], [

			// Console
			/(nintendo|playstation)\s([wids34portablevu]+)/i,                   // Nintendo/Playstation

			// GNU/Linux based
			/(mint)[\/\s\(]?(\w+)*/i,                                           // Mint
			/(mageia|vectorlinux)[;\s]/i,                                       // Mageia/VectorLinux
			/(joli|[kxln]?ubuntu|debian|[open]*suse|gentoo|arch|slackware|fedora|mandriva|centos|pclinuxos|redhat|zenwalk|linpus)[\/\s-]?([\w\.-]+)*/i,
			// Joli/Ubuntu/Debian/SUSE/Gentoo/Arch/Slackware
			// Fedora/Mandriva/CentOS/PCLinuxOS/RedHat/Zenwalk/Linpus
			/(hurd|linux)\s?([\w\.]+)*/i,                                       // Hurd/Linux
			/(gnu)\s?([\w\.]+)*/i                                               // GNU
		], [NAME, VERSION], [

			/(cros)\s[\w]+\s([\w\.]+\w)/i                                       // Chromium OS
		], [[NAME, 'Chromium OS'], VERSION],[

			// Solaris
			/(sunos)\s?([\w\.]+\d)*/i                                           // Solaris
		], [[NAME, 'Solaris'], VERSION], [

			// BSD based
			/\s([frentopc-]{0,4}bsd|dragonfly)\s?([\w\.]+)*/i                   // FreeBSD/NetBSD/OpenBSD/PC-BSD/DragonFly
		], [NAME, VERSION],[

			/(ip[honead]+)(?:.*os\s*([\w]+)*\slike\smac|;\sopera)/i             // iOS
		], [[NAME, 'iOS'], [VERSION, /_/g, '.']], [

			/(mac\sos\sx)\s?([\w\s\.]+\w)*/i,
			/(macintosh|mac(?=_powerpc)\s)/i                                    // Mac OS
		], [[NAME, 'Mac OS'], [VERSION, /_/g, '.']], [

			// Other
			/((?:open)?solaris)[\/\s-]?([\w\.]+)*/i,                            // Solaris
			/(haiku)\s(\w+)/i,                                                  // Haiku
			/(aix)\s((\d)(?=\.|\)|\s)[\w\.]*)*/i,                               // AIX
			/(plan\s9|minix|beos|os\/2|amigaos|morphos|risc\sos|openvms)/i,
			// Plan9/Minix/BeOS/OS2/AmigaOS/MorphOS/RISCOS/OpenVMS
			/(unix)\s?([\w\.]+)*/i                                              // UNIX
		], [NAME, VERSION]
		]
	};


	/////////////////
	// Constructor
	////////////////


	var UAParser = function (uastring, extensions) {

		if (!(this instanceof UAParser)) {
			return new UAParser(uastring, extensions).getResult();
		}

		var ua = uastring || ((window && window.navigator && window.navigator.userAgent) ? window.navigator.userAgent : EMPTY);
		var rgxmap = extensions ? util.extend(regexes, extensions) : regexes;

		this.getBrowser = function () {
			var browser = mapper.rgx.apply(this, rgxmap.browser);
			browser.major = util.major(browser.version);
			return browser;
		};
		this.getCPU = function () {
			return mapper.rgx.apply(this, rgxmap.cpu);
		};
		this.getDevice = function () {
			return mapper.rgx.apply(this, rgxmap.device);
		};
		this.getEngine = function () {
			return mapper.rgx.apply(this, rgxmap.engine);
		};
		this.getOS = function () {
			return mapper.rgx.apply(this, rgxmap.os);
		};
		this.getResult = function() {
			return {
				ua      : this.getUA(),
				browser : this.getBrowser(),
				engine  : this.getEngine(),
				os      : this.getOS(),
				device  : this.getDevice(),
				cpu     : this.getCPU()
			};
		};
		this.getUA = function () {
			return ua;
		};
		this.setUA = function (uastring) {
			ua = uastring;
			return this;
		};
		this.setUA(ua);
		return this;
	};

	UAParser.VERSION = LIBVERSION;
	UAParser.BROWSER = {
		NAME    : NAME,
		MAJOR   : MAJOR, // deprecated
		VERSION : VERSION
	};
	UAParser.CPU = {
		ARCHITECTURE : ARCHITECTURE
	};
	UAParser.DEVICE = {
		MODEL   : MODEL,
		VENDOR  : VENDOR,
		TYPE    : TYPE,
		CONSOLE : CONSOLE,
		MOBILE  : MOBILE,
		SMARTTV : SMARTTV,
		TABLET  : TABLET,
		WEARABLE: WEARABLE,
		EMBEDDED: EMBEDDED
	};
	UAParser.ENGINE = {
		NAME    : NAME,
		VERSION : VERSION
	};
	UAParser.OS = {
		NAME    : NAME,
		VERSION : VERSION
	};


	///////////
	// Export
	//////////


	// check js environment
	if (typeof(exports) !== UNDEF_TYPE) {
		// nodejs env
		if (typeof module !== UNDEF_TYPE && module.exports) {
			exports = module.exports = UAParser;
		}
		exports.UAParser = UAParser;
	} else {
		// requirejs env (optional)
		if (typeof(define) === FUNC_TYPE && define.amd) {
			define(function () {
				return UAParser;
			});
		} else {
			// browser env
			window.UAParser = UAParser;
		}
	}

	// jQuery/Zepto specific (optional)
	// Note:
	//   In AMD env the global scope should be kept clean, but jQuery is an exception.
	//   jQuery always exports to global scope, unless jQuery.noConflict(true) is used,
	//   and we should catch that.
	var $ = window.jQuery || window.Zepto;
	if (typeof $ !== UNDEF_TYPE) {
		var parser = new UAParser();
		$.ua = parser.getResult();
		$.ua.get = function() {
			return parser.getUA();
		};
		$.ua.set = function (uastring) {
			parser.setUA(uastring);
			var result = parser.getResult();
			for (var prop in result) {
				$.ua[prop] = result[prop];
			}
		};
	}

})(typeof window === 'object' ? window : this);

