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

// APS standard config phase
(function () {
	"use strict";

	var iMSCP = iMSCP || {};
	iMSCP.apsStandard = angular.module("apsStandard", ["ngTable", "ngResource", "jQueryUI", "dialogService"]);

	iMSCP.apsStandard.config(['$httpProvider', function ($httpProvider) {
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
					if (response.data.message) {
						$window.scrollTo(0, 0);
						$("<div>", {
							"class": "success",
							"html": $.parseHTML(response.data.message),
							"hide": true
						}).prependTo(".body").trigger('message_timeout');
					}

					if (response.data.redirect) {
						window.location.replace(response.data.redirect);
					}

					return response;
				},

				// Global XHR response (error) handler
				responseError: function (rejection) {
					if (rejection.status == 403) {
						window.location.replace("/index.php");
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

	// APS standard package resource
	iMSCP.apsStandard.factory("PackageResource", function ($resource) {
		return $resource("aps_packages.php", {id: '@id'}, {
			query: {isArray: false},
			update: {method: 'PUT'},
			updateIndex: {method: 'POST'}
		});
	});

	// APS standard package controller
	iMSCP.apsStandard.controller("PackageController", packageController);
	packageController.$inject = ["$scope", "NgTableParams", "PackageResource", "$window", 'dialogService'];

	function packageController($scope, NgTableParams, PackageResource, $window, dialogService) {
		var vm = this;

		// Load table data
		vm.loadTable = function () {
			vm.categories = PackageResource.query().$promise.then(function (data) {
				if (data.total_packages) {
					$scope.total_packages = data.total_packages;
					vm.tableParams = new NgTableParams(
						{count: 5},
						{dataset: data.packages, counts: [5, 10, 25, 50]}
					);
					$scope.$watch(function watchSearch() {
						return vm.search;
					}, function (n) {
						vm.tableParams.filter({$: n});
					});

					// Build category list
					var categories = data.packages.reduce(function (results, item) {
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
						"html": $.parseHTML(imscp_i18n.core.aps_standard.no_package_available),
						"hide": true
					}).prependTo(".PackageList");
					return [];
				}
			});
		};

		vm.loadTable();

		// Show package details
		$scope.showDetails = function () {
			PackageResource.get({}, {id: this.package.id}).$promise.then(function (data) {
				dialogService.open("Package details", "/templates.php?tpl=shared/partials/aps_standard/package_details.tpl", data, {
					title: imscp_i18n.core.aps_standard.package_details,
					modal: true,
					width: $($window).width() / 2
				});
			});
		};

		// Lock/Unlock package
		$scope.changeStatus = function (newStatus, prevStatus) {
			var self = this;
			self.package.status = newStatus;
			return PackageResource.update({}, self.package, null, function () {
				self.package.status = prevStatus;
			});
		};

		// Update package index
		vm.updateIndex = function () {
			$window.scrollTo(0, 0);
			$("<div>", {
				"class": "static_warning",
				"html": $.parseHTML(imscp_i18n.core.aps_standard.update_in_progress),
				"hide": true
			}).prependTo(".PackageList");

			return PackageResource.updateIndex(function () {
				$('.static_warning').remove();
				vm.loadTable();
			}).$promise;
		};

		// Install package
		$scope.install = function() {
			// TODO
		}
	}

	iMSCP.jQueryUI = angular.module("jQueryUI", []);

	// jQuery directives
	iMSCP.jQueryUI.directive('jqButton', function () {
		return {
			restrict: 'E', // says that this directive is only for html elements
			replace: true,
			template: '<input type="button">',
			link: function (scope, element, attrs) {
				element.button();
			}
		};
	});

	// Ajax loader
	iMSCP.apsStandard.directive('ajaxLoader', function ($http) {
		return {
			restrict: 'A',
			scope: false,
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
	angular.module('dialogService', []).service('dialogService', ['$rootScope', '$q', '$compile', '$templateCache', '$http',
		function ($rootScope, $q, $compile, $templateCache, $http) {

			var _this = this;
			_this.dialogs = {};

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

				// Copy options so the change ot close isn't propogated back.
				// Extend is used instead of copy because window references are
				// often used in the options for positioning and they can't be deep
				// copied.
				var dialogOptions = {};
				if (angular.isDefined(options)) {
					angular.extend(dialogOptions, options);
				}

				// Initialize our dialog structure
				var dialog = {scope: null, ref: null, deferred: $q.defer()};

				// Get the template from teh cache or url
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
						dialog.ref.dialog("open");

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
		}
	]);
})();
