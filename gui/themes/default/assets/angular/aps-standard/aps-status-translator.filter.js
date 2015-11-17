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

	angular.module('imscp.aps-standard').filter('apsTranslateStatus', apsTranslateStatus);

	apsTranslateStatus.$inject = ['gettextCatalog'];

	function apsTranslateStatus(gettextCatalog) {
		var translationMap;

		function initTranslationMap() {
			translationMap = {
				toadd: gettextCatalog.getString('Installation in progress...'),
				todelete: gettextCatalog.getString('Uninstallation in progress...'),
				tochange: gettextCatalog.getString('Reinstallation in progress...'),
				unlocked: gettextCatalog.getString('Unlocked'),
				locked: gettextCatalog.getString('Locked'),
				unknown: gettextCatalog.getString('Unexpected status. Please contact your administrator.')
			};
		}

		return function (status) {
			if (!angular.isDefined(translationMap)) {
				initTranslationMap();
			}

			if (translationMap.hasOwnProperty(status)) {
				return translationMap[status];
			}

			return translationMap['unknown'];
		}
	}
})();
