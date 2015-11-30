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

namespace iMSCP\Core\Config;

/**
 * Class DbConfigHandler
 * @package iMSCP\Core\Config
 */
class DbConfigHandler extends AbstractConfigHandler implements \iterator, \Serializable
{
	/**
	 * @var \PDO PDO instance used by objects of this class
	 */
	protected $pdo;

	/**
	 * @var array Array that contains all configuration parameters from the database
	 */
	protected $parameters = array();

	/**
	 * PDOStatement to insert a configuration parameter in the database
	 *
	 * <b>Note:</b> For performance reason, the PDOStatement instance is created only once at the first execution of the
	 * {@link _insert()} method.
	 *
	 * @var \PDOStatement
	 */
	protected $insertStmt = null;

	/**
	 * PDOStatement to update a configuration parameter in the database
	 *
	 * <b>Note:</b> For performances reasons, the PDOStatement instance is created only once at the first execution of
	 * the {@link _update()} method.
	 *
	 * @var \PDOStatement
	 */
	protected $updateStmt = null;

	/**
	 * PDOStatement to delete a configuration parameter in the database
	 *
	 * <b>Note:</b> For performances reasons, the PDOStatement instance is created only once at the first execution of
	 * the {@link _delete()} method.
	 *
	 * @var \PDOStatement
	 */
	protected $deleteStmt = null;

	/**
	 * Variable bound to the PDOStatement instances
	 *
	 * This variable is bound to the PDOStatement instances that are used by {@link _insert()}, {@link _update()} and
	 * {@link _delete()} methods.
	 *
	 * @var string Configuration parameter key name
	 */
	protected $key = null;

	/**
	 * Variable bound to the PDOStatement objects
	 *
	 * This variable is bound to the PDOStatement instances that are used by both {@link _insert()} and
	 * {@link _update()} methods.
	 *
	 * @var mixed Configuration parameter value
	 */
	protected $value = null;

	/**
	 * @var int Counter for SQL update queries
	 */
	protected $insertQueriesCounter = 0;

	/**
	 * @var int Counter for SQL insert queries
	 */
	protected $updateQueriesCounter = 0;

	/**
	 * @var int Counter for SQL delete queries
	 */
	protected $deleteQueriesCounter = 0;

	/**
	 * @var string Database table name for configuration parameters
	 */
	protected $tableName = 'config';

	/**
	 * @var string Database column name for configuration parameters keys
	 */
	protected $keysColumn = 'name';

	/**
	 * @var string Database column name for configuration parameters values
	 */
	protected $valuesColumn = 'value';

	/**
	 * @var bool Internal flag indicating whether or not cached dbconfig object must be flushed
	 */
	protected $flushCache = false;

	/**
	 * Loads all configuration parameters from the database
	 *
	 * <b>Parameters:</b>
	 *
	 * The constructor accepts one or more parameters passed in a array where each key represent a parameter name.
	 *
	 * For an array, the possible parameters are:
	 *
	 * - db: A PDO instance
	 * - table_name: Database table that contain configuration parameters
	 * - key_column: Database column name for configuration parameters key names
	 * - value_column: Database column name for configuration parameters values
	 *
	 * <b>Note:</b> The three last parameters are optionals.
	 *
	 * For a single parameter, only a PDO instance is accepted.
	 *
	 * @param \PDO|array $params A PDO instance or an array of parameters that contains at least a PDO instance
	 */
	public function __construct($params)
	{
		if (is_array($params)) {
			if (!array_key_exists('db', $params) || !($params['db'] instanceof \PDO)) {
				throw new \InvalidArgumentException('A PDO instance is requested for ' . __CLASS__);
			}

			$this->pdo = $params['db'];

			// Overrides the database table name for configuration parameters
			if (isset($params['table_name'])) {
				$this->tableName = $params['table_name'];
			}

			// Override the column name for configuration parameters keys
			if (isset($params['keys_column'])) {
				$this->keysColumn = $params['keys_column'];
			}

			// Set the column name for configuration parameters values
			if (isset($params['values_column'])) {
				$this->valuesColumn = $params['values_column'];
			}

		} elseif (!$params instanceof \PDO) {
			throw new \InvalidArgumentException('PDO instance requested for ' . __CLASS__);
		}

		$this->pdo = $params;
		$this->_loadAll();
	}

	/**
	 * Set PDO instance
	 *
	 * @param \PDO $db
	 */
	public function setPdo(\PDO $db)
	{
		$this->pdo = $db;
	}

	/**
	 * Set table name onto operate
	 *
	 * @param $tableName
	 */
	public function setTable($tableName)
	{
		$this->tableName = (string)$tableName;
	}

	/**
	 * Set key column
	 *
	 * @param $columnName
	 */
	public function setKeyColumn($columnName)
	{
		$this->keysColumn = (string)$columnName;
	}

	/**
	 * Set value column
	 *
	 * @param $columnName
	 */
	public function setValueColumn($columnName)
	{
		$this->valuesColumn = (string)$columnName;
	}

	/**
	 * Allow access as object properties
	 *
	 * @see set()
	 * @param string $key Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Insert or update a configuration parameter in the database
	 *
	 * <b>Note:</b> For performances reasons, queries for updates are only done if old and new value of a parameter are
	 * not the same.
	 *
	 * @param string $key Configuration parameter key name
	 * @param mixed $value Configuration parameter value
	 * @return void
	 */
	public function set($key, $value)
	{
		$this->key = $key;
		$this->value = $value;

		if (!$this->exists($key)) {
			$this->_insert();
		} elseif ($this->parameters[$key] != $value) {
			$this->_update();
		} else {
			return;
		}

		$this->parameters[$key] = $value;
	}

	/**
	 * Retrieve a configuration parameter value
	 *
	 * @param string $key Configuration parameter key name
	 * @return mixed Configuration parameter value
	 */
	public function get($key)
	{
		if (!isset($this->parameters[$key])) {
			throw new \InvalidArgumentException("Configuration variable `$key` is missing.");
		}

		return $this->parameters[$key];
	}

	/**
	 * Checks if a configuration parameters exists
	 *
	 * @param string $key Configuration parameter key name
	 * @return boolean TRUE if configuration parameter exists, FALSE otherwise
	 */
	public function exists($key)
	{
		return array_key_exists($key, $this->parameters);
	}

	/**
	 * Replaces all parameters of this object with parameters from another
	 *
	 * This method replace the parameters values of this object with the same values from another
	 * {@link ArrayConfigHandler} object.
	 *
	 * If a key from this object exists in the second object, its value will be replaced by the value from the second
	 * object. If the key exists in the second object, and not in the first, it will be created in the first object.
	 * All keys in this object that don't exist in the second object will be left untouched.
	 *
	 * <b>Note:</b> This method is not recursive.
	 *
	 * @param ArrayConfigHandler $config iMSCP_Config_Handler object
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function merge(ArrayConfigHandler $config)
	{
		try {
			$this->pdo->beginTransaction();

			parent::merge($config);

			$this->pdo->commit();
		} catch (\PDOException $e) {
			$this->pdo->rollBack();

			return false;
		}

		return true;
	}

	/**
	 * PHP isset() overloading on inaccessible members
	 *
	 * This method is triggered by calling isset() or empty() on inaccessible members.
	 *
	 * <b>Note:</b> This method will return FALSE if the configuration parameter value is NULL. To test existence of a
	 * configuration parameter, you should use the {@link exists()} method.
	 *
	 * @param string $key Configuration parameter key name
	 * @return boolean TRUE if the parameter exists and its value is not NULL
	 */
	public function __isset($key)
	{
		return isset($this->parameters[$key]);
	}

	/**
	 * PHP unset() overloading on inaccessible members
	 *
	 * This method is triggered by calling isset() or empty() on inaccessible members.
	 *
	 * @param  string $key Configuration parameter key name
	 * @return void
	 */
	public function __unset($key)
	{
		$this->del($key);
	}

	/**
	 * Force reload of all configuration parameters from the database
	 *
	 * This method will remove all the current loaded parameters and reload it from the database.
	 *
	 * @return void
	 */
	public function forceReload()
	{
		$this->parameters = array();
		$this->_loadAll();
	}

	/**
	 * Returns the count of SQL queries that were executed
	 *
	 * This method returns the count of queries that were executed since the last call of
	 * {@link reset_queries_counter()} method.
	 *
	 * @param string $queriesCounterType Query counter type (insert|update)
	 * @return int
	 */
	public function countQueries($queriesCounterType)
	{
		switch ($queriesCounterType) {
			case 'update':
				return $this->updateQueriesCounter;
				break;
			case 'insert':
				return $this->insertQueriesCounter;
				break;
			case 'delete':
				return $this->deleteQueriesCounter;
				break;
			default:
				throw new \InvalidArgumentException('Unknown queries counter.');
		}
	}

	/**
	 * Reset a counter of queries
	 *
	 * @param string $queriesCounterType Type of query counter (insert|update|delete)
	 * @return void
	 */
	public function resetQueriesCounter($queriesCounterType)
	{
		switch ($queriesCounterType) {
			case 'update':
				$this->updateQueriesCounter = 0;
				break;
			case 'insert':
				$this->insertQueriesCounter = 0;
				break;
			case 'delete':
				$this->deleteQueriesCounter = 0;
				break;
			default:
				throw new \InvalidArgumentException('Unknown queries counter.');
		}
	}

	/**
	 * Deletes a configuration parameters from the database
	 *
	 * @param string $key Configuration parameter key name
	 * @return void
	 */
	public function del($key)
	{
		$this->key = $key;
		$this->_delete();

		unset($this->parameters[$key]);
	}

	/**
	 * Load all configuration parameters from the database
	 *
	 * @return void
	 */
	protected function _loadAll()
	{
		$query = "SELECT `{$this->keysColumn}`, `{$this->valuesColumn}` FROM `{$this->tableName}`";

		if (($stmt = $this->pdo->query($query, \PDO::FETCH_ASSOC))) {
			$keyColumn = $this->keysColumn;
			$valueColumn = $this->valuesColumn;

			foreach ($stmt->fetchAll() as $row) {
				$this->parameters[$row[$keyColumn]] = $row[$valueColumn];
			}
		} else {
			throw new \RuntimeException('Could not get configuration parameters from database.');
		}
	}

	/**
	 * Store a new configuration parameter in the database
	 *
	 * @return void
	 */
	protected function _insert()
	{
		if (!$this->insertStmt instanceof \PDOStatement) {

			$query = "
				INSERT INTO `{$this->tableName}` (
					`{$this->keysColumn}`, `{$this->valuesColumn}`
				) VALUES (
					:index, :value
				)
			";

			$this->insertStmt = $this->pdo->prepare($query);
		}

		if (!$this->insertStmt->execute(array(':index' => $this->key, ':value' => $this->value))) {
			throw new \PDOException("Unable to insert new entry `{$this->key}` in config table.");
		} else {
			$this->flushCache = true;
			$this->insertQueriesCounter++;
		}
	}

	/**
	 * Update a configuration parameter in the database
	 *
	 * @return void
	 */
	protected function _update()
	{
		if (!$this->updateStmt instanceof \PDOStatement) {
			$query = "
				UPDATE `{$this->tableName}` SET `{$this->valuesColumn}` = :value WHERE `{$this->keysColumn}` = :index
			";

			$this->updateStmt = $this->pdo->prepare($query);
		}

		if (!$this->updateStmt->execute(array(':index' => $this->key, ':value' => $this->value))) {
			throw new \PDOException("Unable to update entry `{$this->key}` in config table.");
		} else {
			$this->flushCache = true;
			$this->updateQueriesCounter++;
		}
	}

	/**
	 * Deletes a configuration parameter from the database
	 *
	 * @return void
	 */
	protected function _delete()
	{
		if (!$this->deleteStmt instanceof \PDOStatement) {
			$query = "DELETE FROM `{$this->tableName}` WHERE `{$this->keysColumn}` = :index";
			$this->deleteStmt = $this->pdo->prepare($query);
		}

		if (!$this->deleteStmt->execute(array(':index' => $this->key))) {
			throw new \PDOException('Unable to delete entry in config table.');
		} else {
			$this->flushCache = true;
			$this->deleteQueriesCounter++;
		}
	}

	/**
	 * Whether or not an offset exists
	 *
	 * @param mixed $offset An offset to check for existence
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->parameters);
	}

	/**
	 * Returns an associative array that contains all configuration parameters
	 *
	 * @return array Array that contains configuration parameters
	 */
	public function toArray()
	{
		return $this->parameters;
	}

	/**
	 * Returns the current element
	 *
	 * @return mixed Returns the current element
	 */
	public function current()
	{
		return current($this->parameters);
	}

	/**
	 * Returns the key of the current element
	 *
	 * @return string|null Return the key of the current element or NULL on failure
	 */
	public function key()
	{
		return key($this->parameters);
	}

	/**
	 * Moves the current position to the next element
	 *
	 * @return void
	 */
	public function next()
	{
		next($this->parameters);
	}

	/**
	 * Rewinds back to the first element of the Iterator.
	 *
	 * <b>Note:</b> This is the first method called when starting a foreach loop. It will not be executed after foreach
	 * loops.
	 *
	 * @return void
	 */
	public function rewind()
	{
		reset($this->parameters);
	}

	/**
	 * Checks if current position is valid
	 *
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function valid()
	{
		return array_key_exists(key($this->parameters), $this->parameters);
	}


	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		return serialize($this->parameters);
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * The string representation of the object.
	 * </p>
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->parameters = unserialize($serialized);
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		if ($this->flushCache) {
			@unlink(DBCONFIG_CACHE_FILE_PATH);
		}
	}
}
