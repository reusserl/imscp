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

namespace iMSCP\ApsStandard\Entity;

use JMS\Serializer\Annotation AS JMS;

/**
 * Class ApsPackageDetails
 *
 * @package iMSCP\ApsStandard\Model
 * @JMS\AccessType("public_method")
 */
class ApsPackageDetails
{
	/**
	 * @var string Package description
	 * @JMS\Type("string")
	 */
	private $description;

	/**
	 * @var array Package screenshots
	 * @JMS\Type("array")
	 */
	private $screenshots = array();

	/**
	 * @var string Package packager
	 * @JMS\Type("string")
	 */
	private $packager;

	/**
	 * @var string Package license
	 * @JMS\Type("string")
	 */
	private $licenseName;

	/**
	 * @var string Package license text
	 * @JMS\Type("string")
	 */
	private $licenseText;

	/**
	 * Get package description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set package description
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Get package screenshots
	 *
	 * @return array
	 */
	public function getScreenshots()
	{
		return $this->screenshots;
	}

	/**
	 * Set package screenshots
	 *
	 * @param array $screenshots
	 * @return void
	 */
	public function setScreenshots($screenshots)
	{
		$this->screenshots = $screenshots;
	}

	/**
	 * Get package packager
	 *
	 * @return string
	 */
	public function getPackager()
	{
		return $this->packager;
	}

	/**
	 * Set package packager
	 *
	 * @param string $packager
	 */
	public function setPackager($packager)
	{
		$this->packager = $packager;
	}

	/**
	 * Get package license name
	 *
	 * @return string
	 */
	public function getLicenseName()
	{
		return $this->licenseName;
	}

	/**
	 * Set package license name
	 *
	 * @param string $licenseName
	 */
	public function setLicenseName($licenseName)
	{
		$this->licenseName = $licenseName;
	}

	/**
	 * Get package license text
	 *
	 * @return string
	 */
	public function getLicenseText()
	{
		return $this->licenseText;
	}

	/**
	 * Set package license text
	 *
	 * @param string $licenseText
	 */
	public function setLicenseText($licenseText)
	{
		$this->licenseText = $licenseText;
	}
}
