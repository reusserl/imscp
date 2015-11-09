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

	angular.module('imscp.notification').directive('notification', notificationDirective);

	notificationDirective.$inject = ['$rootScope'];

	function notificationDirective($rootScope) {
		return {
			restrict: 'EA',
			template: "<div ng-repeat=\"notification in notifications\" ng-class=\"getSeverity(notification)\" ng-switch=\"notification.trustHtml\">\n" +
			"    <div ng-switch-when=\"true\" ng-bind-html=\"notification.text\"></div>\n" +
			"    <div ng-switch-default ng-bind=\"notification.text\"></div>\n" +
			"</div>\n",
			replace: false,
			scope: true,
			controller: ['$scope', '$timeout', function ($scope, $timeout) {
				$scope.notifications = [];

				/**
				 * Handle the given notification
				 *
				 * @param notification
				 */
				function handleNotification(notification) {
					$scope.notifications.push(notification);

					if (notification.timeout && notification.timeout !== -1) {
						$timeout(function () {
							var index = $scope.notifications.indexOf(notification);
							if (index > -1) {
								$scope.notifications.splice(index, 1);
							}
						}, notification.timeout);
					}
				}

				/**
				 * Get notification severity
				 *
				 * @param notification Notification object
				 * @returns {{success: boolean, static_success: boolean, info: boolean, static_info: boolean, warning: boolean, static_warning: boolean, error: boolean, static_error: boolean}}
				 */
				$scope.getSeverity = function (notification) {
					return {
						success: notification.severity === 'success',
						static_success: notification.severity === 'static_success',
						info: notification.severity === 'info',
						static_info: notification.severity === 'static_info',
						warning: notification.severity === 'warning',
						static_warning: notification.severity === 'static_warning',
						error: notification.severity === 'error',
						static_error: notification.severity === 'static_error'
					}
				};

				/**
				 * Notification event listener
				 */
				$rootScope.$on('notification', function (event, notification) {
					handleNotification(notification);
				});
			}]
		}
	}
})();
