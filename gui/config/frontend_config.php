<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

// Service manager configuration file
return array(
	// Service manager (service locator) configuration
	'service_manager' => array(
		'invokables' => array(
			'ApsDependentObjectsDeletionListener' => 'iMSCP\\ApsStandard\\Listener\\ApsDependentObjectsDeletionListener',
			'EncryptionDataService' => 'iMSCP\\Service\\EncryptionDataService'
		),

		'factories' => array(
			// Common services
			'Database' => 'iMSCP\\Service\\DatabaseServiceFactory',
			'ORM' => 'iMSCP\\Service\\ORMServiceFactory',
			'Request' => 'iMSCP\\Service\\HttpRequestServiceFactory',
			'Response' => 'iMSCP\\Service\\HttpResponseServiceFactory',
			'Serializer' => 'iMSCP\\Service\\SerializerServiceFactory',
			'Translator' => 'iMSCP\\Service\\TranslatorServiceFactory',
			'Validator' => 'iMSCP\\Service\\ValidatorServiceFactory'
		),

		'abstract_factories' => array(
			// Abstract factory for APS standard controllers
			'iMSCP\\ApsStandard\\Controller\\ApsControllerAbstractFactory',

			// Abstract factory for APS standard services
			'iMSCP\\ApsStandard\\Service\\ApsServiceAbstractFactory'
		),

		'aliases' => array(
			'EntityManager' => 'ORM'
		)
	),

	// Listener aggregates
	'listeners' => array(
		'ApsDependentObjectsDeletionListener'
	)
);
