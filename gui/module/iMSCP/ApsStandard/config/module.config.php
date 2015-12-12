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

return [
    // Service manager configuration for the ApsStandard module
    'service_manager' => [
        'invokables' => [
            'aps_standard.update_package_index' => 'iMSCP\ApsStandard\Command\UpdatePackageIndexCommand',
            'ApsStandardListener' => 'iMSCP\ApsStandard\Listener\ApsStandardListener'
        ],
        'abstract_factories' => [
            // Abstract factory for APS standard controllers
            'iMSCP\ApsStandard\Controller\ApsControllerAbstractFactory',

            // Abstract factory for APS standard services
            'iMSCP\ApsStandard\Service\ApsServiceAbstractFactory'
        ],
    ],

    // Listener aggregates
    'listeners' => [
        'ApsStandardListener'
    ],

    'doctrine_integration' => [
        'driver' => [
            'default_driver' => [
                'paths' => [
                    './module/iMSCP/ApsStandard/src/Entity'
                ]
            ],
            'default' => [
                'drivers' => [
                    'iMSCP\ApsStandard\Entity' => 'default_driver'
                ]
            ]
        ],
        'configuration' => [
            'default' => [
                'entity_namespaces' => [
                    'Aps' => 'iMSCP\ApsStandard\Entity'
                ]
            ]
        ]
    ],
];
