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

namespace iMSCP\ApsStandard;

use iMSCP_Registry as Registry;

/**
 * Class ApsStandard
 * @package iMSCP\ApsStandard
 */
abstract class ApsStandardAbstract
{
	/**
	 * @var array List of supported repositories (APS format specifications)
	 */
	protected $supportedRepositories = array(
		'1',
		'1.1',
		'1.2',
		// '2.0' Not supported yet (must add routines to handle new schema)
	);

	/**
	 * @var string APS service URL
	 **/
	protected $serviceURL = 'http://apscatalog.com';

	/**
	 * @var string APS package metadata directory
	 */
	protected $packageMetadataDir;

	/**
	 * @var string APS packages directory
	 */
	protected $packageDir;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$config = Registry::get('config');
		$this->setPackageMetadataDir($config['CACHE_DATA_DIR'] . '/aps_standard/metadata');
		$this->setPackageDir($config['CACHE_DATA_DIR'] . '/aps_standard/packages');
	}

	/**
	 * Get APS catalog URL
	 *
	 * @return string
	 */
	public function getServiceURL()
	{
		return $this->serviceURL;
	}

	/**
	 * Set APS catalog URL
	 *
	 * @param string $url URL
	 * @return void
	 */
	public function setServiceURL($url)
	{
		$this->serviceURL = (string)$url;
	}

	/**
	 * Get package metadata directory
	 *
	 * @return string
	 */
	public function getPackageMetadataDir()
	{
		return $this->packageMetadataDir;
	}

	/**
	 * Set package metadata directory
	 *
	 * @param string $packageMetadataDir
	 */
	public function setPackageMetadataDir($packageMetadataDir)
	{
		$packageMetadataDir = (string)$packageMetadataDir;
		$this->packageMetadataDir = rtrim($packageMetadataDir, '/');
	}

	/**
	 * Get packages directory
	 *
	 * @return string
	 */
	public function getPackageDir()
	{
		return $this->packageDir;
	}

	/**
	 * Set packages directory
	 *
	 * @param string $packageDir
	 */
	public function setPackageDir($packageDir)
	{
		$packageDir = (string)$packageDir;
		$this->packageDir = rtrim($packageDir, '/');
	}
}
