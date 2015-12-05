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

namespace iMSCP\Core\Plugin;

use Zend\EventManager\Event;

/**
 * Custom event for use with plugin manager
 * Composes Plugin objects
 *
 * @method PluginManager getTarget
 */
class PluginEvent extends Event
{
    /**
     * Plugin events triggered by event manager
     */
    const onBeforeUpdatePluginList = 'onBeforeUpdatePluginList';
    const onAfterUpdatePluginList = 'onAfterUpdatePluginList';
    const onBeforeInstallPlugin = 'onBeforeInstallPlugin';
    const onAfterInstallPlugin = 'onAfterInstallPlugin';
    const onBeforeUpdatePlugin = 'onBeforeUpdatePlugin';
    const onAfterUpdatePlugin = 'onAfterUpdatePlugin';
    const onBeforeEnablePlugin = 'onBeforeEnablePlugin';
    const onAfterEnablePlugin = 'onAfterEnablePlugin';
    const onBeforeDisablePlugin = 'onBeforeDisablePlugin';
    const onAfterDisablePlugin = 'onAfterDisablePlugin';
    const onBeforeUninstallPlugin = 'onBeforeUninstallPlugin';
    const onAfterUninstallPlugin = 'onAfterUninstallPlugin';
    const onBeforeDeletePlugin = 'onBeforeDeletePlugin';
    const onAfterDeletePlugin = 'onAfterDeletePlugin';
    const onBeforeLockPlugin = 'onBeforeLockPlugin';
    const onAfterLockPlugin = 'onAfterLockPlugin';
    const onBeforeUnlockPlugin = 'onBeforeUnlockPlugin';
    const onAfterUnlockPlugin = 'onAfterUnlockPlugin';
    const onBeforePluginsRoute = 'onBeforePluginsRoute';
    const onAfterPluginsRoute = 'onAfterPluginsRoute';
    const onBeforeProtectPlugin = 'onBeforeProtectPlugin';
    const onAfterProtectPlugin = 'onAfterProtectPlugin';

    /**
     * @var AbstractPlugin
     */
    protected $plugin;

    /**
     * @var string
     */
    protected $pluginName;

    /**
     * Get the name of a given plugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Set the name of a given plugin
     *
     * @param  string $pluginName
     * @return PluginEvent
     */
    public function setPluginName($pluginName)
    {
        if (!is_string($pluginName)) {
            throw new \InvalidArgumentException(
                sprintf('%s expects a string as an argument; %s provided', __METHOD__, gettype($pluginName))
            );
        }

        $this->pluginName = $pluginName;

        return $this;
    }

    /**
     * Get plugin
     *
     * @return AbstractPlugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * Set plugin to compose in this event
     *
     * @param AbstractPlugin $plugin
     * @return PluginEvent
     */
    public function setPlugin($plugin)
    {
        if (!$plugin instanceof AbstractPlugin) {
            throw new \InvalidArgumentException(
                sprintf('%s expects an AbstractPlugin object as an argument; %s provided', __METHOD__, gettype($plugin))
            );
        }
        // Performance tweak, don't add it as param.
        $this->plugin = $plugin;
        return $this;
    }
}
