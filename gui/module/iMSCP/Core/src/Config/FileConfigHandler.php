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
 * Class FileConfigHandler
 * @package iMSCP\Core\Config
 */
class FileConfigHandler extends AbstractConfigHandler
{
	/**
	 * Configuration file path
	 *
	 * @var string
	 */
	protected $configFilePath;

	/**
	 * Loads all configuration parameters from a flat file
	 *
	 * @param string $configFilePath Configuration file path
	 */
	public function __construct($configFilePath)
	{
		$this->configFilePath = $configFilePath;
		$this->_parseFile();
	}

	/**
	 * Opens a configuration file and parses its Key = Value pairs
	 *
	 * @return void
	 */
	protected function _parseFile()
	{
		if (($fd = @file_get_contents($this->configFilePath)) == false) {
			throw new \RuntimeException(sprintf('Unable to open the configuration file `%s`', $this->configFilePath));
		}

		foreach (explode(PHP_EOL, $fd) as $line) {
			if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
				list($key, $value) = explode('=', $line, 2);
				$this[trim($key)] = trim($value);
			}
		}
	}
}
