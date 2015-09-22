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

var iMSCP = iMSCP || {};
iMSCP.apsStandard = angular.module("apsStandard", ["ngTable", "ngResource", "jQueryUI"]);

// APS standard package resource
(function () {
	"use strict";
	iMSCP.apsStandard.config(['$httpProvider', function ($httpProvider) {
		$httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
	}]);

	iMSCP.apsStandard.factory("PackageResource", ['$resource', function ($resource) {
		return $resource("aps_packages.php", { id: '@id' }, {
			update: { method: 'PUT' }
		});
	}]);
})();

// APS standard package controller
(function () {
	"use strict";

	iMSCP.apsStandard.controller("PackageController", ['$scope', 'NgTableParams', 'PackageResource', function (
		$scope, NgTableParams, PackageResource
	) {
		$scope.packages = PackageResource.query(function (packages) {
			$scope.tableParams = new NgTableParams({ count: 5 }, {
				dataset: packages, counts: [ 5, 10, 25, 30, 50 ]
			});
		});

		// Lock/Unlock package
		$scope.changeStatus = function (newStatus, prevStatus) {
			this.package.status = newStatus;
			this.package.$update().catch(function (response) {
				response.config.data.status = prevStatus;
			});
		};
	}]);
})();

iMSCP.jQueryUI = angular.module("jQueryUI", []);

// jQuery directives
(function () {
	"use strict";
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
})();

// Ajax loader
(function($) {
	"use strict";
	iMSCP.apsStandard.directive('ajaxLoader', ['$http' , function ($http) {
		return {
			restrict: 'A',
			scope: false,
			link: function (scope, elm, attrs) {
				scope.isLoading = function () {
					return $http.pendingRequests.length > 0;
				};

				scope.$watch(scope.isLoading, function (v) {
					if(v) {
						$("body").addClass("loading");
					} else {
						$("body").removeClass("loading");
						elm.show();
					}
				});
			}
		}
	}]);
})(jQuery);
