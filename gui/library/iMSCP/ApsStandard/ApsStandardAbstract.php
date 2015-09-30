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

/**
 * Class ApsStandard
 * @package iMSCP\ApsStandard
 */
abstract class ApsStandardAbstract
{
	/**
	 * @var array List of supported APS format specifications
	 */
	protected $apsVersions = array(
		'1',
		'1.1',
		'1.2',
		// '2.0' Not yet supported (must add routines to handle new schema)
	);

	/**
	 * @var string APS catalog URL
	 **/
	protected $apsCatalogURL = 'http://apscatalog.com';

	/**
	 * @var string APS package metadata directory
	 */
	protected $packageMetadataDir;

	/**
	 * @var string APS packages directory
	 */
	protected $packagesDir;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->setPackageMetadataDir(GUI_ROOT_DIR . '/data/persistent/aps/meta');
		$this->setPackagesDir(GUI_ROOT_DIR . '/data/persistent/aps/packages');
	}

	/**
	 * Get APS catalog URL
	 *
	 * @return string
	 */
	public function getAPScatalogURL()
	{
		return $this->apsCatalogURL;
	}

	/**
	 * Set APS catalog URL
	 *
	 * @param string $url URL
	 * @return void
	 */
	public function setAPScatalogURL($url)
	{
		$this->apsCatalogURL = (string)$url;
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
	public function getPackagesDir()
	{
		return $this->packagesDir;
	}

	/**
	 * Set packages directory
	 *
	 * @param string $packagesDir
	 */
	public function setPackagesDir($packagesDir)
	{
		$packagesDir = (string)$packagesDir;
		$this->packagesDir = rtrim($packagesDir, '/');
	}
}
