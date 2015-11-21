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

	angular.module('imscp.json-form').directive('jsonFormField', jsonFormField);

	jsonFormField.$inject = ['$compile', '$http', '$q', '$window'];

	function jsonFormField($compile, $http, $q, $window) {
		function loadTemplate(fieldType) {
			var templateUrl = '/assets/angular/json-form/';

			if (fieldType == 'integer' || fieldType == 'float') {
				templateUrl += 'number.html';
			} else if (fieldType == 'domain-name' || fieldType == 'host-name') {
				templateUrl += 'string.html';
			} else {
				templateUrl += fieldType + '.html';
			}

			return $http.get(templateUrl, {cache: true}).then(
				function (response) {
					return response.data;
				},
				function () {
					return $q.reject("json-form: Template " + templateUrl + " was not found.");
				}
			);
		}

		return {
			restrict: 'E',
			replace: true,
			//require: '^form',
			scope: {
				field: '='
			},
			link: function (scope, element) {
				loadTemplate(scope.field.metadata.type).then(function (html) {
					element.html(html);

					if (scope.field.metadata.type == 'enum') {
						scope.field['value'] = scope.field.metadata.choices[0];
					}

					return $compile(element.contents())(scope);
				}, function (rejectionReason) {
					//console.log(rejectionReason);
					$window.location.replace('/errors/404.html');
				});
			}
		};
	}
})();
