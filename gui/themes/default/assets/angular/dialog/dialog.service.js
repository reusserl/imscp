/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jason Stadler
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * UTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN TH
 * SOFTWARE.
 */

(function () {
	'use strict';

	angular.module('imscp.dialog').service('DialogService', DialogService);

	DialogService.$inject = ['$rootScope', '$q', '$compile', '$templateCache', '$http'];

	function DialogService($rootScope, $q, $compile, $templateCache, $http) {
		var _this = this;
		_this.dialogs = {};
		$.ui.dialog.prototype._focusTabbable = $.noop;

		this.open = function (id, template, model, options) {
			// Check our required arguments
			if (!angular.isDefined(id)) {
				throw 'dialogService requires id in call to open';
			}

			if (!angular.isDefined(template)) {
				throw 'dialogService requires template in call to open';
			}

			// Set the defaults for model
			if (!angular.isDefined(model)) {
				model = null;
			}

			// Copy options so the change ot close isn't propagated back. Extend is used instead of copy because
			// window references are often used in the options for positioning and they can't be deep copied.
			var dialogOptions = {autoOpen: false};
			if (angular.isDefined(options)) {
				//angular.extend(dialogOptions, options);
				angular.merge(dialogOptions, options);
			}

			// Initialize our dialog structure
			var dialog = {scope: null, ref: null, deferred: $q.defer()};

			// Get the template from the cache or url
			loadTemplate(template).then(
				function (dialogTemplate) {
					// Create a new scope, inherited from the parent.
					dialog.scope = $rootScope.$new();
					dialog.scope.model = model;
					var dialogLinker = $compile(dialogTemplate);
					dialog.ref = $(dialogLinker(dialog.scope));

					// Handle the case where the user provides a custom close and also the case where the user clicks
					// the X or ESC and doesn't call close or cancel.
					var customCloseFn = dialogOptions.close;
					dialogOptions.close = function (event, ui) {
						if (customCloseFn) {
							customCloseFn(event, ui);
						}
						cleanup(id);
					};

					// Initialize the dialog and open it
					dialog.ref.dialog(dialogOptions);
					dialog.ref.dialog('open');

					// Cache the dialog
					_this.dialogs[id] = dialog;

				}, function (error) {
					throw error;
				}
			);

			// Return our cached promise to complete later
			return dialog.deferred.promise;
		};

		this.close = function (id, result) {
			// Get the dialog and throw exception if not found
			var dialog = getExistingDialog(id);
			// Notify those waiting for the result. This occurs first because the close calls the close handler on
			// the dialog whose default action is to cancel.
			dialog.deferred.resolve(result);
			// Close the dialog (must be last)
			dialog.ref.dialog("close");
		};

		this.cancel = function (id) {
			// Get the dialog and throw exception if not found
			var dialog = getExistingDialog(id);
			// Notify those waiting for the result. This occurs first because the cancel calls the close handler on
			// the dialog whose default action is to cancel.
			dialog.deferred.reject();
			// Cancel and close the dialog (must be last)
			dialog.ref.dialog('close');
		};

		function cleanup(id) {
			// Get the dialog and throw exception if not found
			var dialog = getExistingDialog(id);
			// This is only called from the close handler of the dialog in case the x or escape are used to cancel
			// the dialog. Don't call this from close, cancel, or externally.
			dialog.deferred.reject();
			dialog.scope.$destroy();
			// Remove the object from the DOM
			dialog.ref.remove();
			// Delete the dialog from the cache
			delete _this.dialogs[id];
		}

		function getExistingDialog(id) {
			// Get the dialog from the cache
			var dialog = _this.dialogs[id];

			// Throw an exception if the dialog is not found
			if (!angular.isDefined(dialog)) {
				throw 'DialogService does not have a reference to dialog id ' + id;
			}
			return dialog;
		}

		// Since IE8 doesn't support string.trim, provide a manual method.
		function trim(string) {
			return string ? string.replace(/^\s+|\s+$/g, '') : string;
		}

		// Loads the template from cache or requests and adds it to the cache
		function loadTemplate(template) {
			var deferred = $q.defer();
			var html = $templateCache.get(template);

			if (angular.isDefined(html)) {
				// The template was cached or a script so return it
				html = trim(html);
				deferred.resolve(html);
			} else {
				// Retrieve the template if it is a URL
				return $http.get(template, {cache: $templateCache}).then(
					function (response) {
						var html = response.data;
						if (!html || !html.length) {
							// Nothing was found so reject the promise
							return $q.reject('Template ' + template + ' was not found');
						}
						html = trim(html);
						// Add it to the template cache using the url as the key
						$templateCache.put(template, html);
						return html;
					}, function () {
						return $q.reject('Template ' + template + ' was not found');
					}
				);
			}
			return deferred.promise;
		}
	}
})();
