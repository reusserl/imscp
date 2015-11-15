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

	angular.module('imscp.jq-ui').directive('jqConfirm', jqConfirm);

	jqConfirm.$inject = ['gettextCatalog'];

	function jqConfirm(gettextCatalog) {
		var i = 0;
		return {
			restrict: 'A',
			priority: 1,
			compile: function (element, attrs) {
				var fn = '$$jqConfirm' + i++, _ngClick = attrs.ngClick;

				attrs.ngClick = fn + '($event)';

				return function (scope, element, attrs) {
					var confirmMsg = attrs.jqConfirm || gettextCatalog.getString('Are you sure?');
					scope[fn] = function (event) {
						$('<div/>', {
							id: 'confirmDialog',
							html: confirmMsg
						}).dialog({
							appendTo: 'body',
							title: 'Confirm dialog',
							modal: true,
							buttons: [
								{
									text: gettextCatalog.getString('Ok'),
									click: function () {
										$(this).dialog("close").remove();
										scope.$apply(_ngClick, {$event: event});
									}
								},
								{
									text: gettextCatalog.getString('Cancel'),
									click: function () {
										$(this).dialog("close").remove();
									}
								}
							],
							close: function () {
								$('button,input').blur();
							}
						});
					};
				};
			}
		};
	}
})();
