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

	angular.module('imscp.aps-standard.aps-instance').controller('NewApsInstance', NewApsInstance);

	NewApsInstance.$inject = ['ApsInstanceResource', 'DialogService', '$window'];

	function NewApsInstance(ApsInstanceResource, DialogService, $window) {
		var vm = this;

		vm.newInstanceModal = function (pkg) {
			ApsInstanceResource.new({}, {id: pkg.id}).$promise.then(function (model) {
				DialogService.open('newApsInstanceModal', '/templates.php?tpl=assets/angular/aps-standard/aps-instance/new-aps-instance.tpl', model, {
					title: sprintf(imscp_i18n.core.aps.new_app_instance, pkg.name),
					modal: true,
					width: $($window).width() / 2,
					position: {'my': 'center', at: 'top+180'},
					buttons: [
						{
							text: imscp_i18n.core.aps.install,
							click: $.noop,
							type: 'submit',
							form: 'settingsForm'
						},
						{
							text: imscp_i18n.core.aps.cancel,
							click: function () {
								$(this).dialog('close');
							}
						}
					],
					close: function () {
						$("button,input").blur();
					},
					open: function () {
						$(this).find('[type=submit]').hide();
					}
				});
			});
		};

		vm.createAction = function (model) {
			model.$save({id: model.package_id}, function () {
				dialogService.close('newApsInstanceModal');
			}, function (response) {
				if (response.data.errors) {
					model.errors = response.data.errors; // Replace with notification this.notification(notifications, type, target)
				}
			});
		}
	}
})();
