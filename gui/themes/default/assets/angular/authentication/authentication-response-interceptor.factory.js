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

	angular.module('imscp.authentication').factory('AuthenticationResponseInterceptor', AuthenticationResponseInterceptor);

	AuthenticationResponseInterceptor.$inject = ['$rootScope', '$q', 'AUTH_EVENTS'];

	function AuthenticationResponseInterceptor($rootScope, $q, AUTH_EVENTS) {
		return {
			responseError: function (response) {
				$rootScope.$broadcast({
					401: AUTH_EVENTS.notAuthenticated,
					403: AUTH_EVENTS.notAuthorized
				}[response.status], response);

				return $q.reject(response);
			}
		}
	}
})();
