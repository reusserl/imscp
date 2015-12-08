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

namespace iMSCP\Core\Plugin\Listener;

use iMSCP\Core\Plugin\PluginEvent;

/**
 * Class PluginResolverListener
 *
 * This is the default plugin resolver listener which assumes that the plugins are in the iMSCP\Plugin namespace.
 * It is possible to provide any other resolver logic by providing your own plugin resolver.
 *
 * @package iMSCP\Core\Plugin\Listener
 */
class PluginResolverListener
{
    /**
     * Resolve a plugin
     *
     * @param pluginEvent $event
     * @return string|false FALSE if the plugin class does not exist
     */
    public function __invoke(PluginEvent $event)
    {
        $class = 'iMSCP\\Plugin\\' . $event->getPluginName();

        if (!class_exists($class)) {
            return false;
        }

        return $class;
    }
}
