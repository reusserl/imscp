/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

(function () {
	"use strict";
	var iMSCP = iMSCP || {};
	iMSCP.aps = angular.module("aps", ["ngTable", "ngResource", "jQueryUI", "dialogService", "angular.filter", "ngSanitize", "EscapeHtml"]).config(['$httpProvider', function ($httpProvider) {
		// Make i-MSCP aware of XHR requests
		$httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
		// Disable caching
		$httpProvider.defaults.headers.common["Cache-Control"] = "no-cache";
		$httpProvider.defaults.headers.common.Pragma = "no-cache";
		$httpProvider.defaults.headers.common["If-Modified-Since"] = "0";
		// Global response handler
		$httpProvider.interceptors.push(function ($q, $window) {
			return {
				// Global XHR response (succes) handler
				response: function (response) {
					if(response.data.redirect) {
						window.location.replace(response.data.redirect);
					}

					if (response.data.message) {
						$window.scrollTo(0, 0);
						$("<div>", {
							"class": "success",
							"html": $.parseHTML(response.data.message),
							"hide": true
						}).prependTo(".body").trigger('message_timeout');
					}



					return response;
				},

				// Global XHR response (error) handler
				responseError: function (rejection) {
					if (rejection.data.redirect) {
						window.location.replace(rejection.data.redirect);
					}

					if (rejection.data.message) {
						$window.scrollTo(0, 0);
						$("<div>", {
							"class": "error",
							"html": $.parseHTML(rejection.data.message),
							"hide": true
						}).prependTo(".body").trigger('message_timeout');
					}

					return $q.reject(rejection);
				}
			}
		})
	}]);

	// RESOURCES

	// Package resource
	iMSCP.aps.factory("PackageResource", function ($resource) {
		return $resource("aps_packages.php", {id: '@id'}, {
			update: {method: 'PUT'},
			updateIndex: {method: 'POST'}
		});
	});

	// Instance resource
	iMSCP.aps.factory("InstanceResource", function ($resource) {
		return $resource("aps_instances.php", {id: '@id'}, {
			update: {method: 'PUT'},
			"new":{method: 'GET', params: {action: "new"}, isArray: true }
		});
	});

	// CONTROLLERS

	// Package controller
	iMSCP.aps.controller("PackageController", packageController);
	packageController.$inject = ["$scope", "NgTableParams", "PackageResource", "$window", 'dialogService'];
	function packageController($scope, NgTableParams, PackageResource, $window, dialogService) {
		var vm = this;

		// Load table data
		vm.loadTable = function () {
			vm.categories = PackageResource.query().$promise.then(function (data) {
				if (data.length) {
					$scope.total_packages = data.length;
					vm.tableParams = new NgTableParams({count: 5}, {dataset: data, counts: [5, 10, 25, 50]});

					$scope.$watch(function watchSearch() {
						return vm.search;
					}, function (n) {
						vm.tableParams.filter({$: n});
					});

					// Build category list
					var categories = data.reduce(function (results, item) {
						var category = item.category.toUpperCase();
						if (results.indexOf(category) < 0) {
							results.push(category);
						}
						return results;
					}, []).map(function (category) {
						return {id: category, title: category};
					});

					// Sort categories
					categories.sort(function (a, b) {
						if (a.title > b.title) {
							return 1;
						}
						if (a.title < b.title) {
							return -1;
						}
						return 0;
					});

					return categories;
				} else {
					$("<div>", {
						"class": "static_info",
						"html": $.parseHTML(imscp_i18n.core.aps.no_package_available),
						"hide": true
					}).prependTo(".PackageList");
					return [];
				}
			});
		};

		vm.loadTable();

		// Show package
		$scope.showAction = function () {
			var pkg = this.package;
			PackageResource.get({}, {id: this.package.id}).$promise.then(function (data) {
				data = angular.merge(data, pkg);
				dialogService.open("PackageDetails", "/templates.php?tpl=shared/aps_standard/package_details.tpl", data, {
					title: imscp_i18n.core.aps.package_details,
					modal: true,
					width: $($window).width() / 2
				});
			});
		};

		// Update package
		$scope.updateAction = function (newStatus, prevStatus) {
			var self = this;
			self.package.status = newStatus;
			self.package.$update(null, null, function() { self.package.status = prevStatus; vm.loadTable(); });
		};

		// Update package index
		$scope.updateIndexAction = function () {
			/*
			$window.scrollTo(0, 0);
			$("<div>", {
				"class": "static_warning",
				"html": $.parseHTML(imscp_i18n.core.aps.update_in_progress),
				"hide": true
			}).prependTo(".PackageList");
			*/

			PackageResource.updateIndex(function () {
				$('.static_warning').remove();
			});
		};
	}

	// Instance controller
	iMSCP.aps.controller("WriteInstanceController", writeInstanceController);
	writeInstanceController.$inject = ["InstanceResource", "$window", 'dialogService', "$q"];
	function writeInstanceController( InstanceResource, $window, dialogService) {
		var vm = this;

		// Get new instance form
		vm.newAction = function(pkg) {
			InstanceResource.new({}, {id: pkg.id}).$promise.then(function(data) {
				dialogService.open("InstanceNew", "/templates.php?tpl=shared/aps_standard/instance_new.tpl", data, {
					title: sprintf(imscp_i18n.core.aps.new_app_instance, pkg.name),
					modal: true,
					width: $($window).width() / 2,
					position: { 'my': 'center', 'at': 'top+190' },
					buttons: {
						"Cancel": function() {
							$( this ).dialog( "close" );
						}
					}
				});
			});
		};

		vm.createAction = function (data) {
			console.log(data);
			console.log(angular.toJson(data, true));
			// TODO redirect to instances.php on success
		}
	}

	// Filter
	iMSCP.aps.filter('strToRegexp', function() {
		return function(str) {
			if(str !== '') {
				str = '/' + str + '/';
				var flags = str.replace(/.*\/([gimy]*)$/, '$1');
				var pattern = str.replace(new RegExp('^/(.*?)/' + flags + '$'), '$1');
				return new RegExp(pattern, flags);
			}

			return undefined;
		}
	});

	// FORM

	iMSCP.aps.directive('imscpForm', function() {
		return {
			restrict: 'E', // says that this directive is only for html elements
			templateUrl: "/templates.php?tpl=shared/aps_standard/instance_settings_form.tpl"
		}
	}).directive('imscpFormField', function($q, $compile, $templateCache, $http) {
		// Load the given template
		var loadTemplate = function(fieldType) {
			var deferred = $q.defer();
			var template;

			if(fieldType == 'text' || fieldType == 'email' ||  fieldType == 'password') {
				template = 'template/form/fields/string.tpl';
			} else if(fieldType == 'boolean') {
				template = 'template/form/fields/boolean.tpl';
			} else {
				template = 'template/form/fields/enum.tpl';
			}

			template = $templateCache.get(template);

			if (angular.isDefined(template)) {
				deferred.resolve(template);
			} else {
				$http.get(tpl, {cache: $templateCache}).then(function (data) {
					deferred.resolve(data);
				}, function() {
					deferred.reject("Template " + template + " was not found");
				});
			}

			return deferred.promise;
		};

		// Compile the given template
		var compileTemplate = function(template, scope) {
			var deferred = $q.defer();

			loadTemplate(template).then(function(template){
				//deferred.resolve($compile(tpl));
				deferred.resolve($compile(template)(scope));
			});

			return deferred.promise;
		};

		var linker = function(scope, elm) {
			compileTemplate(scope.field.metadata.type, scope).then(function(template) {
				//elm.html(template(scope));
				elm.html(template);
			});
		};

		return {
			restrict: 'E', // says that this directive is only for html elements
			template: '<div ng-bind="field"></div>',
			scope: {
				field: '='
			},
			link: linker
		}
	});

	// AJAX LOADER

	// Ajax loader
	iMSCP.aps.directive('ajaxLoader', function ($http) {
		return {
			restrict: 'A', // Say that this directive is only for html attributes
			link: function (scope, elm, attrs) {
				scope.isLoading = function () {
					return $http.pendingRequests.length > 0;
				};

				scope.$watch(scope.isLoading, function (v) {
					if (v) {
						$("body").addClass("loading");
					} else {
						$("body").removeClass("loading");
					}
				});
			}
		}
	});

	// jQuery compat module
	iMSCP.jQueryUI = angular.module("jQueryUI", []).
		directive('jqButton', function () {
			return {
				restrict: 'E', // says that this directive is only for html elements
				replace: true,
				template: '<input type="button">',
				link: function (scope, element, attrs) {
					element.button();
				}
			};
		}).
		directive('jqTooltip', function() {
			return {
				restrict: 'E', // says that this directive is only for html elements
				replace: true,
				template: '<span>',
				link: function(scope, element, attrs) {
					element.tooltip({ tooltipClass: "ui-tooltip-notice", track: true });
				}
			}
		});

	angular.module("EscapeHtml", []).filter('escapeHtml', function () {
		var entityMap = {
			"&": "&amp;",
			"<": "&lt;",
			">": "&gt;",
			'"': '&quot;',
			"'": '&#39;',
			"/": '&#x2F;'
		};

		return function(str) {
			return String(str).replace(/[&<>"'\/]/g, function (s) {
				return entityMap[s];
			});
		}
	});
})();

// jQuery dialog service
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
	angular.module('dialogService', []).service('dialogService', ['$rootScope', '$q', '$compile', '$templateCache', '$http', "$timeout",
		function ($rootScope, $q, $compile, $templateCache, $http, $timeout) {

			var _this = this;
			_this.dialogs = {};
			$.ui.dialog.prototype._focusTabbable = function(){};

			this.open = function (id, template, model, options) {
				// Check our required arguments
				if (!angular.isDefined(id)) {
					throw "dialogService requires id in call to open";
				}

				if (!angular.isDefined(template)) {
					throw "dialogService requires template in call to open";
				}

				// Set the defaults for model
				if (!angular.isDefined(model)) {
					model = null;
				}

				// Copy options so the change ot close isn't propagated back.
				// Extend is used instead of copy because window references are
				// often used in the options for positioning and they can't be deep
				// copied.
				var dialogOptions = { autoOpen:false };
				if (angular.isDefined(options)) {
					//angular.extend(dialogOptions, options);
					angular.merge(dialogOptions, options);
				}

				// Initialize our dialog structure
				var dialog = { scope: null, ref: null, deferred: $q.defer() };

				// nxw
				dialog.scope = $rootScope.$new();
				dialog.scope.model = model;

				compileTemplate(template, dialog.scope).then(function(html) {
					dialog.ref = $(html);
					// Initialize the dialog and open it
					dialog.ref.dialog(dialogOptions);
					//setTimeout(function(){dialog.ref.dialog("open");}, 3000);
					dialog.ref.dialog("open");
					// Cache the dialog
					_this.dialogs[id] = dialog;
				});
				// nxw

				/*
				// Get the template from the cache or url
				loadTemplate(template).then(
					function (dialogTemplate) {
						// Create a new scope, inherited from the parent.
						dialog.scope = $rootScope.$new();
						dialog.scope.model = model;
						var dialogLinker = $compile(dialogTemplate);
						dialog.ref = $(dialogLinker(dialog.scope));

						// Handle the case where the user provides a custom close and also
						// the case where the user clicks the X or ESC and doesn't call
						// close or cancel.
						var customCloseFn = dialogOptions.close;
						dialogOptions.close = function (event, ui) {
							if (customCloseFn) {
								customCloseFn(event, ui);
							}
							cleanup(id);
						};

						// Initialize the dialog and open it
						dialog.ref.dialog(dialogOptions);
						//setTimeout(function(){ dialog.ref.dialog("open"); }, 1000);
						dialog.ref.dialog("open");

						// Cache the dialog
						_this.dialogs[id] = dialog;

					}, function (error) {
						throw error;
					}
				);
				*/

				// Return our cached promise to complete later
				return dialog.deferred.promise;
			};

			this.close = function (id, result) {
				// Get the dialog and throw exception if not found
				var dialog = getExistingDialog(id);
				// Notify those waiting for the result
				// This occurs first because the close calls the close handler on the
				// dialog whose default action is to cancel.
				dialog.deferred.resolve(result);
				// Close the dialog (must be last)
				dialog.ref.dialog("close");
			};

			this.cancel = function (id) {
				// Get the dialog and throw exception if not found
				var dialog = getExistingDialog(id);
				// Notify those waiting for the result
				// This occurs first because the cancel calls the close handler on the
				// dialog whose default action is to cancel.
				dialog.deferred.reject();
				// Cancel and close the dialog (must be last)
				dialog.ref.dialog("close");
			};

			function cleanup(id) {
				// Get the dialog and throw exception if not found
				var dialog = getExistingDialog(id);
				// This is only called from the close handler of the dialog
				// in case the x or escape are used to cancel the dialog. Don't
				// call this from close, cancel, or externally.
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
					throw "DialogService does not have a reference to dialog id " + id;
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
								return $q.reject("Template " + template + " was not found");
							}
							html = trim(html);
							// Add it to the template cache using the url as the key
							$templateCache.put(template, html);
							return html;
						}, function () {
							return $q.reject("Template " + template + " was not found");
						}
					);
				}
				return deferred.promise;
			}

			// NXW addon to defer
			function compileTemplate(template, scope) {
				var deferred = $q.defer();

				loadTemplate(template).then(function(template){
					deferred.resolve($compile(template)(scope));
				});

				return deferred.promise;
			}
		}
	]);
})();
