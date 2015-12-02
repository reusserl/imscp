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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;

/**
 * Class DbConfigHandler
 * @package iMSCP\Core\Config
 */
class DbConfigHandler extends AbstractConfigHandler
{
	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * @var array Array Configuration parameters
	 */
	protected $parameters = [];

	/**
	 * @var Statement Insert statement
	 */
	protected $insertStmt;

	/**
	 * @var Statement Update statement
	 */
	protected $updateStmt;

	/**
	 * @var Statement Delete statement
	 */
	protected $deleteStmt;

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
	protected $tableName;

	/**
	 * @var string Database column name for configuration parameter keys
	 */
	protected $keyColumn;

	/**
	 * @var string Database column name for configuration parameter values
	 */
	protected $valueColumn;

	/**
	 * Constructor
	 *
	 * @param array $params Parameters
	 */
	public function __construct(array $params)
	{
		if (!isset($params['connection']) || !($params['connection'] instanceof Connection)) {
			throw new \InvalidArgumentException('A \Doctrine\DBAL\Connection instance is required.');
		}

		if (!isset($params['table_name'])) {
			throw new \InvalidArgumentException("Missing 'table_name' parameter.");
		}

		if (!isset($params['key_column'])) {
			throw new \InvalidArgumentException("Missing 'key_column' parameter.");
		}

		if (!isset($params['value_column'])) {
			throw new \InvalidArgumentException("Missing 'value_column' parameter.");
		}

		$this->setConnection($params['connection']);
		$this->setTable($params['table_name']);
		$this->setKeyColumn($params['key_column']);
		$this->setValueColumn($params['value_column']);
		$this->loadConfig();
	}

	/**
	 * Set database connection
	 *
	 * @param Connection $connection
	 */
	public function setConnection(Connection $connection)
	{
		$this->connection = $connection;
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
	 * @param string $keyColumn
	 */
	public function setKeyColumn($keyColumn)
	{
		$this->keyColumn = (string)$keyColumn;
	}

	/**
	 * Set value column
	 *
	 * @param string $valueColumn
	 */
	public function setValueColumn($valueColumn)
	{
		$this->valueColumn = (string)$valueColumn;
	}

	/**
	 * Returns the count of SQL queries that were executed
	 *
	 * This method returns the count of queries that were executed since the last call of
	 * {@link resetQueriesCounter()} method.
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
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value)
	{
		if (!$this->offsetExists($offset)) {
			if (null === $this->insertStmt) {
				$this->insertStmt = $this->connection->prepare(
					"INSERT INTO `$this->tableName` (`$this->keyColumn`, `$this->valueColumn`) VALUES (:key, :value)"
				);
			}

			if (!$this->insertStmt->execute([':key' => $offset, ':value' => $value])) {
				throw new \RuntimeException(sprintf(
					"Could not insert the `%s` configuration parameter in the `%s` table.", $offset, $this->tableName
				));
			}

			$this->insertQueriesCounter++;
		} else {
			if (null === $this->updateStmt) {
				$this->updateStmt = $this->connection->prepare(
					"UPDATE `$this->tableName` SET `$this->valueColumn` = :value WHERE `$this->keyColumn` = :key"
				);
			}

			if (!$this->updateStmt->execute([':key' => $offset, ':value' => $value])) {
				throw new \RuntimeException(sprintf(
					"Could not update the `%s` configuration parameter in the `%s` table.", $offset, $this->tableName
				));
			}

			$this->updateQueriesCounter++;
		}

		$this->parameters[$offset] = $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset)
	{
		if (!$this->offsetExists($offset)) {
			if (null === $this->deleteStmt) {
				$this->deleteStmt = $this->connection->prepare(
					"DELETE FROM `$this->tableName` WHERE `$this->keyColumn` = :key"
				);
			}

			if (!$this->deleteStmt->execute([':key' => $offset])) {
				throw new \RuntimeException(sprintf(
					"Could not delete the `%s` configuration parameter in the `%s` table.", $offset, $this->tableName
				));
			}

			$this->deleteQueriesCounter++;
			unset($this->parameters[$offset]);
		}
	}

	/**
	 * Load configuration parameters from database
	 *
	 * @return void
	 */
	protected function loadConfig()
	{
		try {
			$stmt = $this->connection->query("SELECT `$this->keyColumn`, `$this->valueColumn` FROM `$this->tableName`");

			if ($stmt) {
				$keyColumn = $this->keyColumn;
				$valueColumn = $this->valueColumn;

				while ($row = $stmt->fetch()) {
					$this->parameters[$row[$keyColumn]] = $row[$valueColumn];
				}
				//foreach ($stmt->fetchAll() as $row) {
				//	$this->parameters[$row[$keyColumn]] = $row[$valueColumn];
				//}
			}
		} catch (DBALException $e) {
			throw new \RuntimeException(
				sprintf('Could not load configuration parameters from database: %s', $e->getMessage()),
				$e->getCode(),
				$e
			);
		}
	}
}
