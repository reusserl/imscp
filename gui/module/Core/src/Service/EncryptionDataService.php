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

namespace iMSCP\Core\Service;

use iMSCP_Config_Handler_File as ConfigFileHandler;
use iMSCP_Registry as Registry;

/**
 * Class EncryptionDataService
 * @package iMSCP\Service
 */
class EncryptionDataService
{
	/**
	 * @var string Encryption key
	 */
	protected $key;

	/**
	 * @var string Initialization vector
	 */
	protected $iv;

	/**
	 * Constructor
	 *
	 * @throws \iMSCP_Exception
	 */
	public function __construct()
	{
		$config = Registry::get('config');
		$data = new ConfigFileHandler($config['CONF_DIR'] . '/imscp-db-keys');

		if (!isset($data['KEY']) || !isset($data['IV'])) {
			throw new \RuntimeException('Encryption data file (imscp-db-keys) is corrupted.');
		}

		$this->key = $data['KEY'];
		$this->iv = $data['IV'];
	}

	/**
	 * Get encryption key
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Return initialization vector
	 *
	 * @return string
	 */
	public function getIv()
	{
		return $this->iv;
	}
}
