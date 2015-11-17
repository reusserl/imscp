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

	angular.module('imscp.json-form').directive('jsonFormFields', jsonFormFields);

	function jsonFormFields() {
		return {
			restrict: 'E',
			scope: {
				fields: '='
			},
			templateUrl: '/assets/angular/json-form/fields.html'
		}
	}
})();

/**
 * The form attribute can be used to associate a submit button with a form, even if the button is not a child of the <form> itself.
 *
 * This polyfill uses a support check taken from Modernizr and polyfills the functionality using jQuery.
 * See http://tjvantoll.com/2013/07/10/creating-a-jquery-ui-dialog-with-a-submit-button/
 */
(function () {
	// Via Modernizr
	function formAttributeSupport() {
		var form = document.createElement("form"),
			input = document.createElement("input"),
			div = document.createElement("div"),
			id = "formtest" + ( new Date().getTime() ),
			attr,
			bool = false;

		form.id = id;

		// IE6/7 confuses the form id attribute and the form content attribute
		if (document.createAttribute) {
			attr = document.createAttribute("form");
			attr.nodeValue = id;
			input.setAttributeNode(attr);
			div.appendChild(form);
			div.appendChild(input);

			document.documentElement.appendChild(div);
			bool = form.elements.length === 1 && input.form == form;
			div.parentNode.removeChild(div);
		}

		return bool;
	}

	if (!formAttributeSupport()) {
		$(document)
			.on("click", "[type=submit][form]", function (event) {
				event.preventDefault();
				$("#" + $(this).attr("form")).submit();
			})
			.on("keypress", "form input", function (event) {
				if (event.keyCode == 13) {
					var $form = $(this).parents("form");
					if ($form.find("[type=submit]").length == 0 && $("[type=submit][form=" + $(this).attr("form") + "]").length > 0) {
						$form.submit();
					}
				}
			});
	}
}());
