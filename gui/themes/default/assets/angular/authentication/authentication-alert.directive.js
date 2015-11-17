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

	angular.module('imscp.authentication').directive('authenticationAlert', authenticationAlert);

	authenticationAlert.$inject = ['AUTH_EVENTS', 'DialogService', '$window', 'gettextCatalog'];

	function authenticationAlert(AUTH_EVENTS, DialogService, $window, gettextCatalog) {
		return {
			restrict: 'A',
			link: function (scope) {
				var showAlertModal = function () {
					DialogService.open('authenticationAlertModal', '/assets/angular/authentication/authentication-alert-modal.html', {}, {
						title: sprintf(gettextCatalog.getString('Authentication alert')),
						modal: true,
						position: {'my': 'center', at: 'center'},
						buttons: [{
							text: gettextCatalog.getString('Ok'),
							click: function () {
								$(this).dialog('close');
							},
							type: 'button'
						}],
						close: function () {
							$window.location.replace('/index.php');
						}
					});
				};

				scope.$on(AUTH_EVENTS.notAuthenticated, showAlertModal);
			}
		}
	}
})();
