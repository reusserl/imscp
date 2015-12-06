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

require '../application.php';

/** @var \iMSCP\Core\Plugin\PluginManager $pluginManager */
$pluginManager = \iMSCP\Core\Application::getInstance()->getServiceManager()->get('PluginManager');
$pluginEvent =  new \iMSCP\Core\Plugin\PluginEvent();

$plugins = $pluginManager->pluginGetLoaded('Action');
$scriptPath = null;

if (!empty($plugins)) {
    $eventsManager = \iMSCP\Core\Application::getInstance()->getEventManager();

    if (($urlComponents = parse_url($_SERVER['REQUEST_URI'])) !== false) {
        $responses = $eventsManager->trigger(\iMSCP\Core\Plugin\PluginEvent::onBeforePluginsRoute, $pluginManager);

        if (!$responses->stopped()) {
            foreach ($plugins as $plugin) {
                if ($plugin instanceof \iMSCP\Core\Plugin\Feature\RouteLogicProviderInterface) {
                    if (($scriptPath = $plugin->route($urlComponents))) {
                        break;
                    }
                }

                if ($plugin instanceof \iMSCP\Core\Plugin\Feature\RoutesProviderInterface) {
                    foreach ($plugin->getRoutes() as $pluginRoute => $pluginControllerPath) {
                        if ($pluginRoute == $urlComponents['path']) {
                            $scriptPath = $pluginControllerPath;
                            $_SERVER['SCRIPT_NAME'] = $pluginRoute;
                            break;
                        }
                    }
                }

                if ($scriptPath) {
                    break;
                }
            }

            $eventsManager->trigger(\iMSCP\Core\Plugin\PluginEvent::onAfterPluginsRoute, $pluginManager, [
                'scriptPath' => $scriptPath
            ]);

            if ($scriptPath) {
                include_once $scriptPath;
                exit;
            }
        }
    } else {
        throw new RuntimeException(sprintf('Unable to parse URL: %s', $_SERVER['REQUEST_URI']));
    }
}

showNotFoundErrorPage();
