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

	angular.module('imscp.authentication').factory('Authentication', Authentication);

	Authentication.$inject = ['AuthenticationData'];

	function Authentication(AuthenticationData) {
		/**
		 * Does the current user is authenticated?
		 *
		 * @returns {boolean}
		 */
		function isAuthenticated() {
			return !!AuthenticationData.userId;
		}

		/**
		 * Does the current user is authorized?
		 *
		 * @param {string|array} authorizedRoles Authorised roles
		 * @returns {boolean}
		 */
		function isAuthorized(authorizedRoles) {
			if (!angular.isArray(authorizedRoles)) {
				authorizedRoles = [authorizedRoles];
			}

			return (isAuthenticated() && authorizedRoles.indexOf(AuthenticationData.userRole) !== -1);
		}

		// Public API
		return {
			isAuthenticated: isAuthenticated,
			isAuthorized: isAuthorized
		}
	}
})();
