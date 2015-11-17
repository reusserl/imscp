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

namespace iMSCP;

use iMSCP_Config_Handler_File as ConfigFileHandler;
use iMSCP_Events_Manager as EventManager;
use iMSCP_Exception as Exception;
use iMSCP_Registry as Registry;
use iMSCP_Events as Events;
use Zend_Session as SessionHandler;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Events_Event as Event;
use iMSCP_Update_Database as DatabaseUpdater;
use iMSCP_Filter_Compress_Gzip as GzipFilterCompressor;
use iMSCP_Config_Handler_Db as ConfigDbHandler;
use iMSCP\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use iMSCP_Events_ListenerAggregateInterface as ListenerAggregate;

/**
 * Class Initializer
 * @package iMSCP
 */
class Initializer
{
	/**
	 * @var ConfigFileHandler i-MSCP Main configuration
	 */
	protected $mainConfig;

	/**
	 * @var array FrontEnd configuration
	 */
	protected $frontendConfig;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/**
	 * @var ServiceManager
	 */
	protected $serviceManager;

	/**
	 * @staticvar boolean Initialization status
	 */
	protected static $initialized = false;


	/**
	 * Run initializer
	 *
	 * @param ConfigFileHandler $mainConfig
	 * @param $frontendConfig
	 * @return Initializer
	 * @throws Exception
	 */
	public static function run(ConfigFileHandler $mainConfig, $frontendConfig)
	{
		if (self::$initialized) {
			throw new Exception('i-MSCP is already fully initialized.');
		}

		$initializer = new self($mainConfig, $frontendConfig);

		if (PHP_SAPI == 'cli') {
			$initializer->processCLI();
		} elseif (is_xhr()) {
			$initializer->processAjax();
		} else {
			$initializer->processAll();
		}

		return $initializer;
	}

	/**
	 * Singleton - Make new unavailbale
	 *
	 * Create a new Initializer instance that references the given {@link iMSCP_Config_Handler_File} instance.
	 *
	 * @param ConfigFileHandler $config i-MSCP Main configuration
	 * @param array $frontendConfig Frontend configuration
	 * @return Initializer
	 */
	protected function __construct(ConfigFileHandler $config, $frontendConfig)
	{
		// Register config object in registry for further usage.
		$this->mainConfig = Registry::set('config', $config);
		$this->frontendConfig = $frontendConfig;
		$this->eventManager = EventManager::getInstance();
	}

	/**
	 * Make clone unavailable
	 */
	protected function __clone()
	{

	}

	/**
	 * Executes all of the available initialization routines for normal context
	 *
	 * @return void
	 */
	protected function processAll()
	{
		$this->setDisplayErrors();
		$this->initializeServiceManager();
		$this->registerListeners();
		$this->initializeSession();
		$this->initializeDatabase();
		$this->loadConfig();
		$this->setInternalEncoding();
		$this->setTimezone();
		$this->initializeUserGuiProperties();
		$this->initializeLocalization();
		$this->initializeLayout();
		$this->initializeNavigation();
		$this->initializeOutputBuffering();
		$this->checkForDatabaseUpdate();
		$this->initializePlugins();

		// Trigger the onAfterInitialize event
		$this->eventManager->dispatch(Events::onAfterInitialize, array('context' => $this));
		self::$initialized = true;
	}

	/**
	 * Executes all of the available initialization routines for AJAX context
	 *
	 * @return void
	 */
	protected function processAjax()
	{
		$this->setDisplayErrors();
		$this->initializeServiceManager();
		$this->registerListeners();
		$this->initializeSession();
		$this->initializeDatabase();
		$this->loadConfig();
		$this->setInternalEncoding();
		$this->setTimezone();
		$this->initializeUserGuiProperties();
		$this->initializeLocalization();
		$this->initializePlugins();

		// Trigger the onAfterInitialize event
		$this->eventManager->dispatch(Events::onAfterInitialize, array('context' => $this));
		self::$initialized = true;
	}

	/**
	 * Executes all of the available initialization routines for CLI context
	 *
	 * @return void
	 */
	protected function processCLI()
	{
		$this->initializeServiceManager();
		$this->registerListeners();
		$this->initializeDatabase();
		$this->loadConfig();
		$this->initializeLocalization(); // Needed for rebuilt of languages index

		// Trigger the onAfterInitialize event
		$this->eventManager->dispatch(Events::onAfterInitialize, array('context' => $this));
		self::$initialized = true;
	}

	/**
	 * Initialize service manager
	 *
	 * @çeturn void
	 */
	protected function initializeServiceManager()
	{
		$serviceManager = new ServiceManager(new ServiceManagerConfig($this->frontendConfig['service_manager']));
		$this->serviceManager = $serviceManager;
		Registry::set('ServiceManager', $serviceManager);
		Registry::set('ServiceLocator', $serviceManager);
	}

	/**
	 * Register listeners
	 *
	 * @return void
	 */
	protected function registerListeners()
	{
		$eventManager = $this->eventManager;

		if(isset($this->frontendConfig['listeners'])) {
			foreach($this->frontendConfig['listeners'] as $listener) {
				/** @var ListenerAggregate $aggregate */
				$aggregate = $this->serviceManager->get($listener);
				$eventManager->registerAggregate($aggregate);
			}
		}
	}

	/**
	 * Set internal encoding
	 *
	 * @return void
	 */
	protected function setInternalEncoding()
	{
		if (extension_loaded('mbstring')) {
			mb_internal_encoding('UTF-8');
			@mb_regex_encoding('UTF-8');
		}
	}

	/**
	 * Sets the PHP display_errors parameter
	 *
	 * @return void
	 */
	protected function setDisplayErrors()
	{
		if ($this->mainConfig->DEBUG) {
			ini_set('display_errors', 1);
		} else {
			ini_set('display_errors', 0);
		}

		// In any case, write error logs in data/logs/errors.log
		// FIXME Disabled as long file is not rotated
		//ini_set('log_errors', 1);
		//ini_set('error_log', $this->_config->GUI_ROOT_DIR . '/data/logs/errors.log');
	}

	/**
	 * Initialize layout
	 *
	 * @return void
	 */
	protected function initializeLayout()
	{
		// Set layout data (Must be donne at end)
		$this->eventManager->registerListener(
			array(
				Events::onLoginScriptEnd, Events::onLostPasswordScriptEnd, Events::onAdminScriptEnd,
				Events::onResellerScriptEnd, Events::onClientScriptEnd
			),
			'layout_init'
		);


		if (!isset($_SESSION['user_logged'])) {
			$this->eventManager->registerListener(Events::onAfterSetIdentity, function () {
				unset($_SESSION['user_theme_color']);
			});
		}
	}



	/**
	 * Initialize the session
	 *
	 * @throws Exception in case session directory is not writable
	 * @return void
	 */
	protected function initializeSession()
	{
		$sessionDir = $this->mainConfig->GUI_ROOT_DIR . '/data/sessions';

		if (!is_writable($sessionDir)) {
			throw new Exception('The gui/data/sessions directory must be writable.');
		}

		SessionHandler::setOptions(array(
			'use_cookies' => 'on',
			'use_only_cookies' => 'on',
			'use_trans_sid' => 'off',
			'strict' => false,
			'remember_me_seconds' => 0,
			'name' => 'iMSCP_Session',
			'gc_divisor' => 100,
			'gc_maxlifetime' => 1440,
			'gc_probability' => 1,
			'save_path' => $sessionDir
		));
		SessionHandler::start();
	}

	/**
	 * Establishes the connection to the database server
	 *
	 * This methods establishes the default connection to the database server by using configuration parameters that
	 * come from the basis configuration object and then, register the {@link iMSCP_Database} instance in the
	 * {@link iMSCP_Registry} for further usage.
	 *
	 * A PDO instance is also registered in the registry for further usage.
	 *
	 * @throws DatabaseException|Exception
	 * @return void
	 */
	protected function initializeDatabase()
	{
		// For backward compatibility only (components accessing database service using registry)
		Registry::set('db', Registry::get('ServiceManager')->get('Database'));
	}

	/**
	 * Sets timezone
	 *
	 * This method ensures that the timezone is set to avoid any error with PHP versions equal or later than version 5.3.x
	 *
	 * This method acts by checking the `date.timezone` value, and sets it to the value from the i-MSCP PHP_TIMEZONE
	 * parameter if exists and if it not empty or to 'UTC' otherwise. If the timezone identifier is invalid, an
	 * {@link iMSCP_Exception} exception is raised.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function setTimezone()
	{
		// Timezone is not set in the php.ini file?
		if (ini_get('date.timezone') == '') {
			$timezone = (isset($this->mainConfig['TIMEZONE']) && $this->mainConfig['TIMEZONE'] != '')
				? $this->mainConfig['TIMEZONE'] : 'UTC';

			if (!date_default_timezone_set($timezone)) {
				throw new Exception(
					'Invalid timezone identifier set in your imscp.conf file. Please fix this error and re-run the ' .
					'imscp-setup script.'
				);
			}
		}
	}

	/**
	 * Load configuration parameters from the database
	 *
	 * This function retrieves all the parameters from the database and merge them with the basis configuration object.
	 *
	 * Parameters that exists in the basis configuration object will be replaced by those that come from the database.
	 * The basis configuration object contains parameters that come from the i-mscp.conf configuration file or any
	 * parameter defined in the {@link environment.php} file.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function loadConfig()
	{
		/** @var \iMSCP_Database $databaseService */
		$databaseService = Registry::get('ServiceManager')->get('Database');

		/** @var $pdo \PDO */
		$pdo = $databaseService::getRawInstance();

		if (is_readable(DBCONFIG_CACHE_FILE_PATH)) {
			if (!$this->mainConfig['DEBUG']) {
				/** @var ConfigDbHandler $dbConfig */
				$dbConfig = unserialize(file_get_contents(DBCONFIG_CACHE_FILE_PATH));
				$dbConfig->setDb($pdo);
			} else {
				@unlink(DBCONFIG_CACHE_FILE_PATH);
				goto FORCE_DBCONFIG_RELOAD;
			}
		} else {
			FORCE_DBCONFIG_RELOAD:
			// Creating new Db configuration handler.
			$dbConfig = new ConfigDbHandler($pdo);
			if (!$this->mainConfig['DEBUG'] && PHP_SAPI != 'cli') {
				@file_put_contents(DBCONFIG_CACHE_FILE_PATH, serialize($dbConfig), LOCK_EX);
			}
		}

		// Merge main configuration object with the dbConfig object
		$this->mainConfig->merge($dbConfig);

		// Add the dbconfig object into the registry for later use
		Registry::set('dbConfig', $dbConfig);
	}

	/**
	 * Initialize the PHP output buffering / spGzip filter
	 *
	 * Note: The hight level such as 8 and 9 for compression are not recommended for performances reasons. The obtained
	 * gain with these levels is very small compared to the intermediate level such as 6 or 7.
	 *
	 * @return void
	 */
	protected function initializeOutputBuffering()
	{
		if (isset($this->mainConfig->COMPRESS_OUTPUT) && $this->mainConfig->COMPRESS_OUTPUT) {
			// Create a new filter that will be applyed on the buffer output
			/** @var $filter GzipFilterCompressor */
			$filter = Registry::set('bufferFilter', new GzipFilterCompressor(GzipFilterCompressor::FILTER_BUFFER));

			// Show compression information in HTML comment ?
			if (isset($this->mainConfig->SHOW_COMPRESSION_SIZE) && !$this->mainConfig->SHOW_COMPRESSION_SIZE) {
				$filter->compressionInformation = false;
			}

			// Start the buffer and attach the filter to him
			ob_start(array($filter, GzipFilterCompressor::CALLBACK_NAME));
		}
	}

	/**
	 * Load user's GUI properties in session
	 *
	 * @return void
	 */
	protected function initializeUserGuiProperties()
	{
		if (isset($_SESSION['user_id']) && !isset($_SESSION['logged_from']) && !isset($_SESSION['logged_from_id'])) {
			if (!isset($_SESSION['user_def_lang']) || !isset($_SESSION['user_theme'])) {
				$stmt = exec_query('SELECT lang, layout FROM user_gui_props WHERE user_id = ?', $_SESSION['user_id']);

				if ($stmt->rowCount()) {
					$row = $stmt->fetchRow(\PDO::FETCH_ASSOC);

					if ((empty($row['lang']) && empty($row['layout']))) {
						list($lang, $theme) = array($this->mainConfig['USER_INITIAL_LANG'], $this->mainConfig['USER_INITIAL_THEME']);
					} elseif (empty($row['lang'])) {
						list($lang, $theme) = array($this->mainConfig['USER_INITIAL_LANG'], $row['layout']);
					} elseif (empty($row['layout'])) {
						list($lang, $theme) = array($row['lang'], $this->mainConfig['USER_INITIAL_THEME']);
					} else {
						list($lang, $theme) = array($row['lang'], $row['layout']);
					}
				} else {
					list($lang, $theme) = array($this->mainConfig['USER_INITIAL_LANG'], $this->mainConfig['USER_INITIAL_THEME']);
				}

				$_SESSION['user_def_lang'] = $lang;
				$_SESSION['user_theme'] = $theme;
			}
		}
	}

	/**
	 * Initialize localization
	 *
	 * @return void
	 */
	protected function initializeLocalization()
	{
		// For backward compatibility only (components accessing Translator service using registry)
		Registry::set('translator', Registry::get('ServiceManager')->get('Translator'));
	}

	/**
	 * Check for database update
	 *
	 * @return void
	 */
	protected function checkForDatabaseUpdate()
	{
		$this->eventManager->registerListener(
			array(Events::onLoginScriptStart, Events::onBeforeSetIdentity),
			function ($event) {
				if (DatabaseUpdater::getInstance()->isAvailableUpdate()) {
					Registry::get('config')->MAINTENANCEMODE = true;

					/** @var $event Event */
					if (($identity = $event->getParam('identity', null))) {
						if (
							$identity->admin_type != 'admin' &&
							(!isset($_SESSION['logged_from_type']) || $_SESSION['logged_from_type'] != 'admin')
						) {
							set_page_message(
								tr('Only administrators can login when maintenance mode is activated.'), 'error'
							);
							redirectTo('index.php?admin=1');
						}
					}
				}
			}
		);
	}

	/**
	 * Register callback to load navigation file
	 *
	 * @return void
	 */
	protected function initializeNavigation()
	{
		$this->eventManager->registerListener(
			array(Events::onAdminScriptStart, Events::onResellerScriptStart, Events::onClientScriptStart),
			'layout_loadNavigation'
		);
	}

	/**
	 * Initialize plugins
	 *
	 * @throws Exception When a plugin cannot be loaded
	 * @return void
	 */
	protected function initializePlugins()
	{
		/** @var PluginManager $pluginManager */
		$pluginManager = Registry::set('pluginManager', new PluginManager(GUI_ROOT_DIR . '/plugins'));

		foreach ($pluginManager->pluginGetList() as $pluginName) {
			if (!$pluginManager->pluginHasError($pluginName)) {
				if (!$pluginManager->pluginLoad($pluginName)) {
					throw new Exception(sprintf('Unable to load plugin: %s', $pluginName));
				}
			}
		}
	}
}
