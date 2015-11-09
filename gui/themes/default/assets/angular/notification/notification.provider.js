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

	angular.module('imscp.notification').provider('notification', notification);

	notification.$inject = [];

	function notification() {
		var
			_timeout = null,
			_severity = 'info',
			_trustHtml = false,
			_httpNotificationsKey = 'notifications';

		/**
		 * Set default notification timeout (time after which a notification is automatically closed)
		 *
		 * @param timeout Message timeout
		 */
		this.setGlobalTimeout = function (timeout) {
			_timeout = timeout;
		};

		/**
		 * Whether or not to trust HTML content
		 *
		 * @param trustHtml
		 */
		this.setGlobalTrustHtml = function (trustHtml) {
			_trustHtml = trustHtml
		};

		/**
		 * Set default notification severity
		 *
		 * @param severity Default notification severity
		 */
		this.setDefaultSeverity = function (severity) {
			_severity = severity;
		};

		/**
		 * Set HTTP notifications key (key used to retrieve notification from HTTP responses)
		 *
		 * @param httpNotificationsKey XHR notification key
		 */
		this.setHttpNotificationsKey = function (httpNotificationsKey) {
			_httpNotificationsKey = httpNotificationsKey;
		};

		/**
		 * HTTP notifications interceptor
		 *
		 * @type {*[]}
		 */
		this.httpNotificationsInterceptor = ['$q', 'notification', function ($q, notificationSrv) {
			function handleNotification(response) {
				if (response.data.hasOwnProperty(_httpNotificationsKey) && response.data[_httpNotificationsKey].length > 0) {
					angular.forEach(response.data[_httpNotificationsKey], function (notification) {
						notificationSrv.notify(notification.text, notification.severity || _severity);
					});
				}
			}

			return {
				response: function (response) {
					handleNotification(response);
					return response;
				},
				responseError: function (response) {
					handleNotification(response);
					return $q.reject(response);
				}
			}
		}];

		this.$get = ['$rootScope', function ($rootScope) {
			/**
			 * Notify user about the given notification
			 *
			 * @param notification Notification text
			 * @param severity Notification severity
			 * @param config OPTIONAL Notification configuration
			 */
			function notify(notification, severity, config) {
				config = config || {};
				$rootScope.$broadcast('notification', {
					text: notification,
					severity: severity || _severity,
					timeout: config.timeout || _timeout,
					trustHtml: config.trustHtml || _trustHtml
				});
			}

			// Public API
			return {
				notify: notify
			}
		}];
	}
})();
