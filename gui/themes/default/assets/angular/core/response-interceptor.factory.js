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

	angular.module('imscp.core').factory('ResponseInterceptorFactory', ResponseInterceptorFactory);

	ResponseInterceptorFactory.$inject = ['$q', '$window'];

	function ResponseInterceptorFactory($q, $window) {
		return {
			responseError: function (rejection) {
				if (rejection.status === 403) {
					$window.location.replace('/index.php');
				}

				if(rejection.status === 500) {
					window.location.replace('/errors/500.html');
				}

				return $q.reject(rejection);
			}
		}
	}
})();
