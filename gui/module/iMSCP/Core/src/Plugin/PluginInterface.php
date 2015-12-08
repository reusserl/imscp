<?php

namespace iMSCP\Core\Plugin;

/**
 * Interface PluginInterface
 * @package iMSCP\Core\Plugin
 */
interface PluginInterface
{
    /**
     * Get plugin name
     *
     * @return string
     */
    public function getName();

    /**
     * Get plugin type
     *
     * @return mixed
     */
    public function getType();

    /**
     * Get plugin info
     *
     * @return array
     */
    public function getInfo();

    /**
     * Get plugin config
     *
     * @return array
     */
    public function getConfig();

    /**
     * Plugin installation
     *
     * This method is automatically called by the plugin manager when the plugin is being installed.
     *
     * @throws \Exception
     * @param PluginManager $pluginManager
     * @return void
     */
    public function install(PluginManager $pluginManager);

    /**
     * Plugin activation
     *
     * This method is automatically called by the plugin manager when the plugin is being enabled (activated).
     *
     * @throws \Exception
     * @param PluginManager $pluginManager
     * @return void
     */
    public function enable(PluginManager $pluginManager);

    /**
     * Plugin deactivation
     *
     * This method is automatically called by the plugin manager when the plugin is being disabled (deactivated).
     *
     * @throws \Exception
     * @param PluginManager $pluginManager
     * @return void
     */
    public function disable(PluginManager $pluginManager);

    /**
     * Plugin update
     *
     * This method is automatically called by the plugin manager when the plugin is being updated.
     *
     * @throws \Exception
     * @param PluginManager $pluginManager
     * @param string $fromVersion Version from which plugin update is initiated
     * @param string $toVersion Version to which plugin is updated
     * @return void
     */
    public function update(PluginManager $pluginManager, $fromVersion, $toVersion);

    /**
     * Plugin uninstallation
     *
     * This method is automatically called by the plugin manager when the plugin is being uninstalled.
     *
     * @throws \Exception
     * @param PluginManager $pluginManager
     * @return void
     */
    public function uninstall(PluginManager $pluginManager);

    /**
     * Plugin deletion
     *
     * This method is automatically called by the plugin manager when the plugin is being deleted.
     *
     * @throws \Exception
     * @param PluginManager $pluginManager
     * @return void
     */
    public function delete(PluginManager $pluginManager);

    /**
     * Get plugin item with error status
     *
     * This method is called by the i-MSCP debugger.
     *
     * Note: *MUST* be implemented by any plugin which manage its own items.
     *
     * @return array
     */
    public function getItemWithErrorStatus();

    /**
     * Set status of the given plugin item to 'tochange'
     *
     * This method is called by the i-MSCP debugger.
     *
     * Note: *MUST* be implemented by any plugin which manage its own items.
     *
     * @param string $table Table name
     * @param string $field Status field name
     * @param int $itemId item unique identifier
     * @return void
     */
    public function changeItemStatus($table, $field, $itemId);

    /**
     * Return count of request in progress
     *
     * This method is called by the i-MSCP debugger.
     *
     * Note: *MUST* be implemented by any plugin which manage its own items.
     *
     * @return int
     */
    public function getCountRequests();
}
