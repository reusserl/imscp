<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

namespace iMSCP\Core\Database;

use iMSCP\Core\Events;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;

/**
 * Class Database
 * @package iMSCP\Core\Database
 */
class Database
{
	/**
	 * @var Database[] Array which contain Database objects, indexed by connection name
	 */
	protected static $instances = array();

	/**
	 * @var Event
	 */
	protected $events;

	/**
	 * @var \PDO PDO instance.
	 */
	protected $pdo = null;

	/**
	 * @var int Error code from last error occurred
	 */
	protected $lastErrorCode = '';

	/**
	 * @var string Message from last error occurred
	 */
	protected $lastErrorMessage = '';

	/**
	 * @var string Character used to quotes a string
	 */
	public $nameQuote = '`';

	/**
	 * @var int Transaction counter which allow nested transactions
	 */
	protected $transactionCounter = 0;

	/**
	 * Singleton - Make new unavailable
	 *
	 * Creates a PDO object and connects to the database.
	 *
	 * According the PDO implementation, a PDOException is raised on error
	 * See {@link http://www.php.net/manual/en/pdo.construct.php} for more information about this issue.
	 *
	 * @throws \PDOException
	 * @param string $user Sql username
	 * @param string $pass Sql password
	 * @param string $type PDO driver
	 * @param string $host Mysql server hostname
	 * @param string $port Mysql server port
	 * @param string $name Database name
	 * @param array $options OPTIONAL Driver options
	 * @return Database
	 */
	private function __construct($user, $pass, $type, $host, $port, $name, $options = array())
	{
		$this->pdo = new \PDO("$type:host=$host;port=$port;dbname=$name", $user, $pass, $options);
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Singleton - Make clone unavailable.
	 */
	private function __clone()
	{

	}

	/**
	 * Return an event manager instance
	 *
	 * @param EventManager $events
	 * @return EventManager
	 */
	public function events(EventManager $events = null)
	{
		if (null !== $events) {
			$this->events = $events;
		} elseif (null === $this->events) {
			$this->events = new EventManager();
		}

		return $this->events;
	}

	/**
	 * Establishes the connection to the database
	 *
	 * Create and returns an new iMSCP_Database object which represents the connection to the database. If a connection
	 * with the same identifier is already referenced, the connection is automatically closed and then, the object is
	 * recreated.
	 *
	 * @param string $user Sql username
	 * @param string $pass Sql password
	 * @param string $type PDO driver
	 * @param string $host Mysql server hostname
	 * @param string $port Mysql server port
	 * @param string $name Database name
	 * @param string $connection OPTIONAL Connection key name
	 * @param array $options OPTIONAL Driver options
	 * @return Database An iMSCP_Database instance that represents the connection to the database
	 */
	public static function connect($user, $pass, $type, $host, $port, $name, $connection = 'default', $options = null)
	{
		if (is_array($connection)) {
			$options = $connection;
			$connection = 'default';
		}

		if (isset(self::$instances[$connection])) {
			self::$instances[$connection] = null;
		}

		return self::$instances[$connection] = new self($user, $pass, $type, $host, $port, $name, (array)$options);
	}

	/**
	 * Returns a database connection object
	 *
	 * Each database connection object are referenced by an unique identifier. The default identifier, if not one is
	 * provided, is 'default'.
	 *
	 * @param string $connection Connection key name
	 * @return Database A Database instance that represents the connection to the database
	 * @todo Rename the method name to 'getConnection' (Sounds better)
	 */
	public static function getInstance($connection = 'default')
	{
		if (!isset(self::$instances[$connection])) {
			throw new \InvalidArgumentException(sprintf("The Database connection %s doesn't exist.", $connection));
		}

		return self::$instances[$connection];
	}

	/**
	 * Returns the PDO object linked to the current database connection object
	 *
	 * @param string $connection Connection unique identifier
	 * @return \PDO A PDO instance
	 */
	public static function getRawInstance($connection = 'default')
	{
		if (!isset(self::$instances[$connection])) {
			throw new \InvalidArgumentException(sprintf("The Database connection %s doesn't exist.", $connection));
		}

		return self::$instances[$connection]->pdo;
	}

	/**
	 * Prepares an SQL statement
	 *
	 * The SQL statement can contains zero or more named or question mark parameters markers for which real values will
	 * be substituted when the statement will be executed.
	 *
	 * See {@link http://www.php.net/manual/en/pdo.prepare.php}
	 *
	 * @param string $sql Sql statement to prepare
	 * @param array $options OPTIONAL Attribute values for the PDOStatement object
	 * @return \PDOStatement A PDOStatement instance or FALSE on failure. If prepared statements are emulated by PDO,
	 *                        FALSE is never returned.
	 */
	public function prepare($sql, $options = null)
	{
		$this->events()->trigger(
			new DatabaseEvent(Events::onBeforeQueryPrepare, array('context' => $this, 'query' => $sql))
		);

		if (is_array($options)) {
			$stmt = $this->pdo->prepare($sql, $options);
		} else {
			$stmt = $this->pdo->prepare($sql);
		}

		$this->events()->trigger(
			new DatabaseStatementEvent(Events::onAfterQueryPrepare, array('context' => $this, 'statement' => $stmt))
		);

		if (!$stmt) {
			$errorInfo = $this->errorInfo();
			$this->lastErrorMessage = $errorInfo[2];

			return false;
		}

		return $stmt;
	}

	/**
	 * Executes a SQL Statement or a prepared statement
	 *
	 * @param mixed $stmt
	 * @param null $parameters
	 * @return bool|DatabaseResultSet
	 */
	public function execute($stmt, $parameters = null)
	{
		if ($stmt instanceof \PDOStatement) {
			$this->events()->trigger(
				new DatabaseStatementEvent(Events::onBeforeQueryExecute, array('context' => $this, 'statement' => $stmt))
			);

			if (null === $parameters) {
				$rs = $stmt->execute();
			} else {
				$rs = $stmt->execute((array)$parameters);
			}
		} elseif (is_string($stmt)) {
			$this->events()->trigger(
				new DatabaseEvent(Events::onBeforeQueryExecute, array('context' => $this, 'query' => $stmt))
			);

			if (is_null($parameters)) {
				$rs = $this->pdo->query($stmt);
			} else {
				$parameters = func_get_args();
				$rs = call_user_func_array(array($this->pdo, 'query'), $parameters);
			}
		} else {
			throw new \InvalidArgumentException('Wrong parameter. Expects either a string or PDOStatement object');
		}

		if ($rs) {
			$stmt = ($rs === true) ? $stmt : $rs;

			$this->events()->trigger(
				new DatabaseStatementEvent(Events::onAfterQueryExecute, array('context' => $this, 'statement' => $stmt))
			);

			return new DatabaseResultSet($stmt);
		} else {
			$errorInfo = is_string($stmt) ? $this->errorInfo() : $stmt->errorInfo();

			if (isset($errorInfo[2])) {
				$this->lastErrorCode = $errorInfo[0];
				$this->lastErrorMessage = $errorInfo[2];
			} else { // WARN (HY093)
				$errorInfo = error_get_last();
				$this->lastErrorMessage = $errorInfo['message'];
			}

			return false;
		}
	}

	/**
	 * Returns the list of the permanent tables from the database
	 *
	 * @param string|null $like
	 * @return array An array which hold list of database tables
	 */
	public function getTables($like = null)
	{
		if ($like) {
			$stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
			$stmt->execute(array($like));
		} else {
			$stmt = $this->pdo->query('SHOW TABLES');
		}

		return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
	}

	/**
	 * Returns the Id of the last inserted row.
	 *
	 * @return string Last row identifier that was inserted in database
	 */
	public function insertId()
	{
		return $this->pdo->lastInsertId();
	}

	/**
	 * Quote identifier
	 *
	 * @param string $identifier Identifier (table or column name)
	 * @return string
	 */
	public function quoteIdentifier($identifier)
	{
		return '`' . str_replace('`', '``', $identifier) . '`';
	}

	/**
	 * Quotes a string for use in a query
	 *
	 * @param string $string The string to be quoted
	 * @param null|int $parameterType Provides a data type hint for drivers that have alternate quoting styles.
	 * @return string A quoted string that is theoretically safe to pass into an SQL statement
	 */
	public function quote($string, $parameterType = null)
	{
		return $this->pdo->quote($string, $parameterType);
	}

	/**
	 * Sets an attribute on the database handle
	 *
	 * See @link http://www.php.net/manual/en/book.pdo.php} PDO guideline for more information about this.
	 *
	 * @param int $attribute Attribute identifier
	 * @param mixed $value Attribute value
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function setAttribute($attribute, $value)
	{
		return $this->pdo->setAttribute($attribute, $value);
	}

	/**
	 * Retrieves a PDO database connection attribute
	 *
	 * @param $attribute
	 * @return mixed Attribute value or NULL on failure
	 */
	public function getAttribute($attribute)
	{
		return $this->pdo->getAttribute($attribute);
	}

	/**
	 * Initiates a transaction
	 *
	 * @link http://php.net/manual/en/pdo.begintransaction.php
	 * @return bool Returns true on success or false on failure.
	 */
	public function beginTransaction()
	{
		if (!$this->transactionCounter) {
			$this->transactionCounter++;
			return $this->pdo->beginTransaction();
		}

		return true;
	}

	/**
	 * Commits a transaction
	 *
	 * @link http://php.net/manual/en/pdo.commit.php
	 * @return bool Returns true on success or false on failure.
	 */
	public function commit()
	{
		if ($this->transactionCounter) {
			if (!--$this->transactionCounter) {
				return $this->pdo->commit();
			}

			return true;
		}

		return false;
	}

	/**
	 * Rolls back a transaction
	 *
	 * @link http://php.net/manual/en/pdo.rollback.php
	 * @return bool Returns true on success or false on failure.
	 */
	public function rollBack()
	{
		if ($this->transactionCounter) {
			$this->transactionCounter = 0;
			return $this->pdo->rollBack();
		}

		return false;
	}

	/**
	 * Gets the last SQLSTATE error code
	 *
	 * @return mixed  The last SQLSTATE error code
	 */
	public function getLastErrorCode()
	{
		return $this->lastErrorCode;
	}

	/**
	 * Gets the last error message
	 *
	 * This method returns the last error message set by the {@link execute()} or {@link prepare()} methods.
	 *
	 * @return string Last error message set by the {@link execute()} or {@link prepare()} methods.
	 */
	public function getLastErrorMessage()
	{
		return $this->lastErrorMessage;
	}

	/**
	 * Stringified error information
	 *
	 * This method returns a stringified version of the error information associated with the last database operation.
	 *
	 * @return string Error information associated with the last database operation
	 */
	public function errorMsg()
	{
		return implode(' - ', $this->pdo->errorInfo());
	}

	/**
	 * Error information associated with the last operation on the database
	 *
	 * This method returns a array that contains error information associated with the last database operation.
	 *
	 * @return array Array that contains error information associated with the last
	 *               database operation
	 */
	public function errorInfo()
	{
		return $this->pdo->errorInfo();
	}

	/**
	 * Returns quote identifier symbol
	 *
	 * @return string Quote identifier symbol
	 */
	public function getQuoteIdentifierSymbol()
	{
		return $this->nameQuote;
	}
}
