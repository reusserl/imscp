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
	private $screenshots = [];

	/**
	 * @var string Package packager
	 * @JMS\Type("string")
	 */
	private $packager;

	/**
	 * @var string Package license
	 * @JMS\Type("string")
	 */
	private $license;

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
	 * @return ApsPackageDetails
	 */
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
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
	 * @return ApsPackageDetails
	 */
	public function setScreenshots($screenshots)
	{
		$this->screenshots = $screenshots;
		return $this;
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
	 * @return ApsPackageDetails
	 */
	public function setPackager($packager)
	{
		$this->packager = $packager;
		return $this;
	}

	/**
	 * Get package license
	 *
	 * @return string
	 */
	public function getLicense()
	{
		return $this->license;
	}

	/**
	 * Set package license
	 *
	 * @param string $license
	 * @return ApsPackageDetails
	 */
	public function setLicense($license)
	{
		$this->license = $license;
		return $this;
	}
}
