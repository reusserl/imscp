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

use Doctrine\ORM\Mapping as ORM;
use iMSCP\Core\Utils\OpcodeCache;

/**
 * Class AbstractPlugin
 * @package iMSCP\Core\Plugin
 */
abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var array Plugin configuration parameters
     */
    private $config = [];

    /**
     * @var array Plugin previous configuration parameters
     */
    private $configPrev = [];

    /**
     * @var bool TRUE if plugin configuration is loaded, FALSE otherwise
     */
    private $isLoadedConfig = false;

    /**
     * @var string Plugin name
     */
    private $pluginName;

    /**
     * @var string Plugin type
     */
    private $pluginType;

    /**
     * @var PluginManager
     */
    private $pluginManager;

    /**
     * Constructor
     *
     * @param PluginManager $pluginManager
     * @return AbstractPlugin
     */
    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * Returns plugin general information
     *
     * Need return an associative array with the following info:
     *
     * author: Plugin author name(s)
     * email: Plugin author email
     * version: Plugin version
     * require_api: Required i-MSCP plugin API version
     * date: Last modified date of the plugin in YYYY-MM-DD format
     * name: Plugin name
     * desc: Plugin short description (text only)
     * url: Website in which it's possible to found more information about the plugin
     * priority: OPTIONAL priority which define priority for plugin backend processing
     *
     * A plugin can provide any other info for its own needs. However, the following keywords are reserved for internal
     * use:
     *
     *  __nversion__      : Contain the last available plugin version
     *  __installable__   : Tell the plugin manager whether or not the plugin is installable
     *  __uninstallable__ : Tell the plugin manager whether or not the plugin can be uninstalled
     * __need_change__    : Tell the plugin manager wheter or not the plugin need change
     * db_schema_version  : Contain the last applied plugin database migration
     *
     * @return array An array containing information about plugin
     */
    function getInfo()
    {
        $file = $this->pluginManager->pluginGetDirectory() . '/' . $this->getName() . '/info.php';
        $info = [];

        if (@is_readable($file)) {
            $info = include($file);
            OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call
        } else {
            if (!file_exists($file)) {
                set_page_message(
                    tr(
                        '%s::getInfo() not implemented and %s not found. This is a bug in the %s plugin which must be reported to the author(s).',
                        get_class($this),
                        $file,
                        $this->getName()
                    ),
                    'warning'
                );
            } else {
                throw new \RuntimeException(tr("Unable to read the %s file.", $file));
            }
        }

        return array_merge(
            [
                'author' => tr('Unknown'),
                'email' => '',
                'version' => '0.0.0',
                'require_api' => '99.0.0',
                'date' => '0000-00-00',
                'name' => $this->getName(),
                'desc' => tr('Not provided'),
                'url' => ''
            ],
            $info
        );
    }

    /**
     * Returns plugin name
     *
     * @return string
     */
    final public function getName()
    {
        if (null === $this->pluginName) {
            $class = get_class($this);
            $this->pluginName = explode('\\', $class)[substr_count($class, '\\')];
        }

        return $this->pluginName;
    }

    /**
     * Returns plugin type
     *
     * @return string
     */
    final public function getType()
    {
        if (null === $this->pluginType) {
            $class = get_parent_class($this);
            $this->pluginType = explode('\\', $class)[substr_count($class, '\\')];
        }

        return $this->pluginType;
    }

    /**
     * Return plugin configuration
     *
     * @return array An associative array which contain plugin configuration
     */
    final public function getConfig()
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return $this->config;
    }

    /**
     * Load plugin configuration from database
     *
     * @return void
     */
    final protected function loadConfig()
    {
        $stmt = exec_query(
            'SELECT plugin_config, plugin_config_prev FROM plugin WHERE plugin_name = ?', $this->getName()
        );

        if ($stmt->rowCount()) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->config = json_decode($row['plugin_config'], true);
            $this->configPrev = json_decode($row['plugin_config_prev'], true);
            $this->isLoadedConfig = true;
        } else {
            $this->config = [];
            $this->configPrev = [];
        }
    }

    /**
     * Return previous plugin configuration
     *
     * @return array An associative array which contain plugin previous configuration
     */
    final public function getConfigPrev()
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return $this->configPrev;
    }

    /**
     * Return plugin configuration from file
     *
     * @return array
     */
    final public function getConfigFromFile()
    {
        $this->isLoadedConfig = false;

        $pluginName = $this->getName();
        $file = $this->pluginManager->pluginGetDirectory() . "/$pluginName/config.php";
        $config = [];

        if (@file_exists($file)) {
            if (@is_readable($file)) {
                $config = include($file);
                OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call

                $file = "plugins/$pluginName.php";

                if (@is_readable($file)) {
                    $localConfig = include($file);
                    OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call

                    if (array_key_exists('__REMOVE__', $localConfig) && is_array($localConfig['__REMOVE__'])) {
                        $config = utils_arrayDiffRecursive($config, $localConfig['__REMOVE__']);

                        if (array_key_exists('__OVERRIDE__', $localConfig) && is_array($localConfig['__OVERRIDE__'])) {
                            $config = utils_arrayMergeRecursive($config, $localConfig['__OVERRIDE__']);
                        }
                    }
                }
            } else {
                throw new \RuntimeException(tr('Unable to read the plugin %s file. Please check file permissions', $file));
            }
        }

        return $config;
    }

    /**
     * Returns the given plugin configuration
     *
     * @param string $paramName Configuration parameter name
     * @param mixed $default Default value returned in case $paramName is not found
     * @return mixed Configuration parameter value or $default if $paramName not found
     */
    final public function getConfigParam($paramName, $default = null)
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return (isset($this->config[$paramName])) ? $this->config[$paramName] : $default;
    }

    /**
     * Returns the given previous plugin configuration
     *
     * @param string $paramName Configuration parameter name
     * @param mixed $default Default value returned in case $paramName is not found
     * @return mixed Configuration parameter value or $default if $paramName not found
     */
    final public function getConfigPrevParam($paramName, $default = null)
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return (isset($this->configPrev[$paramName])) ? $this->configPrev[$paramName] : $default;
    }

    /**
     * Migrate plugin database schema
     *
     * This method provide a convenient way to alter plugins's database schema over the time in a consistent and easy
     * way.
     *
     * This method considers each migration as being a new 'version' of the database schema. A schema starts off with
     * nothing in it, and each migation modifies it to add or remove tables, columns, or entries. Each time a new
     * migration is applied, the 'db_schema_version' info field is updated. This allow to keep track of the last applied
     * database migration.
     *
     * This method can work in both senses update (up) and downgrade (down) modes.
     *
     * USAGE:
     *
     * Any plugin which uses this method *MUST* provide an sql directory at the root of its directory, which contain all
     * migration files.
     *
     * Migration file naming convention:
     *
     * Each migration file must be named using the following naming convention:
     *
     * <version>_<description>.php where:
     *
     * - <version> is the migration version number such as 003
     * - <description> is the migration description such as add_version_confdir_path_prev
     *
     * Resulting to the following migration file:
     *
     * 003_add_version_confdir_path_prev.php
     *
     * Note: version of first migration file *MUST* start to 001 and not 000.
     *
     * Migration file structure:
     *
     * A migration file is a simple PHP file which return an associative array containing exactly two pairs of key/value:
     *
     * - The 'up' key for which the value must be the SQL statement to be executed in the 'up' mode
     * - The 'down' key for which the value must be the SQL statement to be executed in the 'down' mode
     *
     * If one of these keys is missing, the migrateDb method won't complain and will simply continue its work normally.
     * However, it's greatly recommended to always provide both SQL statements as described above.
     *
     * Sample:
     *
     * <code>
     * return array(
     *     'up' => '
     *         ALTER TABLE
     *             php_switcher_version
     *         ADD
     *             version_confdir_path_prev varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL
     *         AFTER
     *             version_binary_path
     *      ',
     *      'down' => '
     *          ALTER TABLE php_switcher_version DROP COLUMN version_confdir_path_prev
     *      '
     * );
     * </code>
     *
     * @throws \Exception When an error occurs
     * @param string $migrationMode Migration mode (up|down)
     * @return void
     */
    protected function migrateDb($migrationMode = 'up')
    {
        $pluginName = $this->getName();
        $pluginManager = $this->pluginManager;
        $sqlDir = $pluginManager->pluginGetDirectory() . '/' . $pluginName . '/sql';

        if (is_dir($sqlDir)) {
            $pluginInfo = $pluginManager->pluginGetInfo($pluginName);
            $dbSchemaVersion = (isset($pluginInfo['db_schema_version'])) ? $pluginInfo['db_schema_version'] : '000';
            $migrationFiles = [];

            /** @var $migrationFileInfo \DirectoryIterator */
            foreach (new \DirectoryIterator($sqlDir) as $migrationFileInfo) {
                if (!$migrationFileInfo->isDot()) {
                    $migrationFiles[] = $migrationFileInfo->getRealPath();
                }
            }

            natsort($migrationFiles);

            if ($migrationMode == 'down') {
                $migrationFiles = array_reverse($migrationFiles);
            }

            try {
                foreach ($migrationFiles as $migrationFile) {
                    if (is_readable($migrationFile)) {
                        if (preg_match('/(\d+)_[^\/]+\.php$/i', $migrationFile, $version)) {
                            if (
                                ($migrationMode == 'up' && $version[1] > $dbSchemaVersion) ||
                                ($migrationMode == 'down' && $version[1] <= $dbSchemaVersion)
                            ) {
                                $migrationFilesContent = include($migrationFile);

                                if (isset($migrationFilesContent[$migrationMode])) {
                                    execute_query($migrationFilesContent[$migrationMode]);
                                }

                                $dbSchemaVersion = $version[1];
                            }
                        } else {
                            throw new \InvalidArgumentException(
                                tr("File %s doesn't look like a migration file.", $migrationFile)
                            );
                        }
                    } else {
                        throw new \RuntimeException(tr('Migration file %s is not readable.', $migrationFile));
                    }
                }

                $pluginInfo['db_schema_version'] = ($migrationMode == 'up') ? $dbSchemaVersion : '000';
                $pluginManager->pluginUpdateInfo($pluginName, $pluginInfo);
            } catch (\Exception $e) {
                $pluginInfo['db_schema_version'] = $dbSchemaVersion;
                $pluginManager->pluginUpdateInfo($pluginName, $pluginInfo);

                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            throw new \Exception(tr("Directory %s doesn't exists.", $sqlDir));
        }
    }
}
