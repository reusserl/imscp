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

	angular.module('imscp.aps-standard.aps-instance').controller('ApsInstanceController', ApsInstanceController);

	ApsInstanceController.$inject = ['ApsInstanceResource', 'ModalService', 'notification', 'gettextCatalog', '$window'];

	function ApsInstanceController(ApsInstanceResource, ModalService, notification, gettextCatalog, $window) {
		var vm = this;

		vm.listInstances = function() {
			vm.instances = ApsInstanceResource.query().$promise.then(function (instances) {
				if (!instances.length) {
					notification.notify(gettextCatalog.getString('No application instance found.'), 'static_info', {timeout: -1});
				}
			});
		};

		vm.newInstanceModal = function (pkg) {
			ApsInstanceResource.new({}, {id: pkg.id}).$promise.then(function (model) {
				ModalService.open('newApsInstanceModal', '/assets/angular/aps-standard/aps-instance/aps-instance-new-instance-modal.tpl', model, {
					title: sprintf(gettextCatalog.getString('New %s application instance'), pkg.name),
					modal: true,
					hide: 'explode',
					width: $($window).width() / 2,
					maxHeight: $($window).height() / 2,
					position: {'my': 'center', at: 'center'},
					buttons: [
						{
							text: gettextCatalog.getString('Install'),
							click: $.noop,
							type: 'submit',
							id: 'submit',
							form: 'NewApsInstanceForm'
						},
						{
							text: gettextCatalog.getString('Cancel'),
							click: function () {
								$(this).dialog('close');
							}
						}
					],
					close: function () {
						$("button,input").blur();
					}
				});
			});
		};

		vm.newInstance = function (model) {
			model.$save({id: model.package_id}, function () {
				ModalService.close('newApsInstanceModal');
			}, function (response) {
				if (response.data.errors) {
					model.errors = response.data.errors;
				}
			});
		};

		vm.reinstallInstance = function (instance) {
			instance.$update().$promise.then(function() {
				notification.notify(gettextCatalog.getString('The application instance has been scheduled for reinstallation.'), 'success');
			});
		};

		vm.deleteInstance = function (instance) {
			instance.$delete().$promise.then(function() {
				notification.notify(gettextCatalog.getString('The application instance has been scheduled for deletion.'), 'success');
			});
		};
	}
})();
