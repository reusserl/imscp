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

	angular.module('imscp.aps-standard.aps-package').controller('ApsPackageController', ApsPackageController);

	ApsPackageController.$inject = ['ApsPackageResource', 'NgTableParams', 'DialogService', 'Authentication', 'USER_ROLES', 'gettextCatalog', 'notification', '$window'];

	function ApsPackageController(ApsPackageResource, NgTableParams, DialogService, Authentication, USER_ROLES, gettextCatalog, notification, $window) {
		var vm = this;

		vm.packageTable = function () {
			vm.isReadyTable = false;
			vm.categories = ApsPackageResource.getCategories().$promise.then(function (data) {
				return data.map(function (resource) {
					return {id: resource.category, title: resource.category.toUpperCase()};
				}).sort(function (a, b) {
					if (a.title > b.title) {
						return 1;
					}
					if (a.title < b.title) {
						return -1;
					}
					return 0;
				});
			}).then(function (categories) {
				if (categories.length) {
					vm.filters = {globalSearch: ''};
					vm.tableParams = new NgTableParams(
						{
							page: 1,
							count: 5,
							filter: vm.filters

						}, {
							filterOptions: {filterDelay: 1000},
							filterDelayThreshold: 1,
							getData: function (params) {
								return ApsPackageResource.query(params.url()).$promise.then(function (data) {
									params.total(data.resourceCount);
									vm.isReadyTable = true;
									return data.resources.map(function (resource) {
										return new ApsPackageResource(resource);
									});
								});
							},
							counts: [5, 10, 25]
						});
				} else if (Authentication.isAuthorized(USER_ROLES.admin)) {
					notification.notify(gettextCatalog.getString('No package available. You must update the package index.'), 'static_info', {timeout: -1});
				} else {
					notification.notify(gettextCatalog.getString('No package available. Please contact your reseller.'), 'static_info', {timeout: -1});
				}

				return categories;
			});
		};

		vm.packageDetailsModal = function (pkg) {
			ApsPackageResource.get({}, {id: pkg.id}).$promise.then(function (data) {
				data = angular.merge(data, pkg);

				DialogService.open('PackageDetails', '/assets/angular/aps-standard/aps-package/aps-package-details-modal.tpl', data, {
					title: gettextCatalog.getString('Package details'),
					modal: true,
					width: $($window).width() / 2,
					maxHeight: $($window).height() / 2
				});
			});
		};

		vm.packageUpdateStatus = function (pkg) {
			var cStatus = pkg.status;
			pkg.status = cStatus == 'unlocked' ? 'locked' : 'unlocked';
			pkg.$update(null, null, function () {
				pkg.status = cStatus;
			});
		};

		vm.packageUpdateIndex = function () {
			ApsPackageResource.updateIndex(function () {
				$window.location = 'aps_packages.php';
			});
		};
	}
})();
