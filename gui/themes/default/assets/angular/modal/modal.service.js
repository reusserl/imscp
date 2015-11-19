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

	angular.module('imscp.modal').service('ModalService', ModalService);

	ModalService.$inject = ['$rootScope', '$q', '$compile', '$http', '$timeout'];

	function ModalService($rootScope, $q, $compile, $http, $timeout) {
		var _this = this;
		_this.modals = {};
		$.ui.dialog.prototype._focusTabbable = angular.noop;

		this.open = function (id, template, model, options) {
			model = model || {};
			options = options || {};

			if (!angular.isDefined(id)) {
				throw 'Modal service requires id in call to open';
			}

			if (!angular.isDefined(template)) {
				throw 'Modal service requires template in call to open';
			}

			// Copy options so the change ot close isn't propagated back. Extend is used instead of copy because
			// window references are often used in the options for positioning and they can't be deep copied.
			options = angular.extend({autoOpen: false}, options);

			// Initialize modal structure
			var modal = {scope: null, ref: null, deferred: $q.defer()};

			loadTemplate(template).then(
				function (dialogTemplate) {

					// Create modal scope.
					modal.scope = $rootScope.$new();

					// Add modal model to scope
					modal.scope.model = model;

					// Compile modal template and link it to the scope
					modal.ref = $compile(dialogTemplate)(modal.scope);

					// Handle the case where the user provides a custom close and also the case where the user clicks
					// the X or ESC and doesn't call close or cancel.
					var customCloseFn = options.close;
					options.close = function (event, ui) {
						if (customCloseFn) {
							customCloseFn(event, ui);
						}

						cleanup(id);
					};

					modal.ref.dialog(options);

					// Wait digest to finish
					$timeout(function () {
						modal.ref.dialog('open');
					});

					// Cache the modal
					_this.modals[id] = modal;
				}, function (error) {
					throw error;
				}
			);

			// Return our cached promise to complete later
			return modal.deferred.promise;
		};

		this.close = function (id, result) {
			// Get the modal and throw exception if not found
			var modal = getExistingModal(id);

			// Notify those waiting for the result. This occurs first because the close calls the close handler on
			// the modal whose default action is to cancel.
			modal.deferred.resolve(result);

			// Close the modal (must be last)
			modal.ref.dialog("close");
		};

		this.cancel = function (id) {
			// Get the modal and throw exception if not found
			var modal = getExistingModal(id);

			// Notify those waiting for the result. This occurs first because the cancel calls the close handler on
			// the modal whose default action is to cancel.
			modal.deferred.reject();

			// Cancel and close the modal (must be last)
			modal.ref.dialog('close');
		};

		function cleanup(id) {
			// Get the modal and throw exception if not found
			var modal = getExistingModal(id);

			// This is only called from the close handler of the modal in case the x or escape are used to cancel
			// the modal. Don't call this from close, cancel, or externally.
			modal.deferred.reject();
			modal.scope.$destroy();

			// Remove the object from the DOM
			modal.ref.remove();

			// Delete the modal from the cache
			delete _this.modals[id];
		}

		function getExistingModal(id) {
			var modal = _this.modals[id];

			if (!angular.isDefined(modal)) {
				throw 'Modal service does not have a reference to modal id ' + id;
			}

			return modal;
		}

		// Loads the template
		function loadTemplate(template) {
			return $http.get(template, {cache: true}).then(function (response) {
				return response.data;
			});
		}
	}
})();
