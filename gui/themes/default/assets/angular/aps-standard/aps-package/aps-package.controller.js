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
	'use strict';

	angular.module('imscp.aps-standard.aps-package').controller('ApsPackage', ApsPackage);

	ApsPackage.$inject = ['$scope', 'ApsPackageResource', 'NgTableParams', 'DialogService', '$window'];

	function ApsPackage($scope, ApsPackageResource, NgTableParams, DialogService, $window) {
		var vm = this;

		// Load table data
		vm.loadTable = function () {
			vm.categories = ApsPackageResource.query().$promise.then(function (data) {
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

		// Package details
		$scope.showAction = function () {
			var pkg = this.package;
			ApsPackageResource.get({}, {id: this.package.id}).$promise.then(function (data) {
				data = angular.merge(data, pkg);

				DialogService.open("PackageDetails", "/templates.php?tpl=angular/aps-standard/package_details.tpl", data, {
					title: imscp_i18n.core.aps.package_details,
					dialogClass: 'ApsStandard',
					modal: true,
					width: $($window).width() / 2,
					maxHeight: $($window).height() / 2
				});
			});
		};

		// Package update
		$scope.updateAction = function (newStatus, prevStatus) {
			var self = this;
			self.package.status = newStatus;
			self.package.$update(null, null, function () {
				self.package.status = prevStatus;
				vm.loadTable();
			});
		};

		// Package index update
		$scope.updateIndexAction = function () {
			if (confirm(imscp_i18n.core.aps.update_index_warning)) {
				ApsPackageResource.updateIndex(function () {
					$('.static_warning').remove();
				});
			}
		};
	}
})();
