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

namespace iMSCP\ApsStandard\Model;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class Package
 * @package iMSCP\ApsStandard\Model
 */
class Package extends ModelAbstract
{
	/**
	 * @var string Package name
	 */
	protected $name;

	/**
	 * @var string Package summary
	 */
	protected $summary;

	/**
	 * @var string Package version
	 */
	protected $version;

	/**
	 * @var int Package release number
	 */
	protected $release;

	/**
	 * @var string Package APS version
	 */
	protected $aps_version;

	/**
	 * @var string Package category
	 */
	protected $category;

	/**
	 * @var string Package vendor
	 */
	protected $vendor;

	/**
	 * @var string Package vendor URI
	 */
	protected $vendor_uri;

	/**
	 * @var string Package URL
	 */
	protected $url;

	/**
	 * @var string Package icon URL
	 */
	protected $icon_url;

	/**
	 * @var string Package certification
	 */
	protected $cert;

	/**
	 * @var string Package status
	 */
	protected $status;

	/**
	 * Get validation metadata
	 *
	 * @param ClassMetadata $metadata
	 */
	public static function loadValidationMetadata(ClassMetadata $metadata)
	{
		// Right now, only the status field is mutable. Thus, we process validation only for that field.
		$metadata->addPropertyConstraints('status', array(
			new Choice(array('choices' => array('ok', 'disabled')))
		));
	}

	/**
	 * Get package name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set package name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get package summary
	 *
	 * @return string
	 */
	public function getSummary()
	{
		return $this->summary;
	}

	/**
	 * Set package summary
	 *
	 * @param string $summary
	 * @return void
	 */
	public function setSummary($summary)
	{
		$this->summary = $summary;
	}

	/**
	 * Get package version
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Set package version
	 *
	 * @param string $version
	 * @return void
	 */
	public function setVersion($version)
	{
		$this->version = $version;
	}

	/**
	 * Get package release
	 *
	 * @return int
	 */
	public function getRelease()
	{
		return $this->release;
	}

	/**
	 * Set package release
	 *
	 * @param int $release
	 * @return void
	 */
	public function setRelease($release)
	{
		$this->release = $release;
	}

	/**
	 * Get package aps version
	 *
	 * @return string
	 */
	public function getApsVersion()
	{
		return $this->aps_version;
	}

	/**
	 * Set package aps version
	 *
	 * @param string $apsVersion
	 * @return void
	 */
	public function setApsVersion($apsVersion)
	{
		$this->aps_version = $apsVersion;
	}

	/**
	 * Get package category
	 *
	 * @return string
	 */
	public function getCategory()
	{
		return $this->category;
	}

	/**
	 * Set package category
	 *
	 * @param string $category
	 * @return void
	 */
	public function setCategory($category)
	{
		$this->category = $category;
	}

	/**
	 * Get package vendor uri
	 *
	 * @return string
	 */
	public function getVendorUri()
	{
		return $this->vendor_uri;
	}

	/**
	 * Set package vendor URI
	 *
	 * @param string $vendorUri
	 * @return void
	 */
	public function setVendorUri($vendorUri)
	{
		$this->vendor_uri = $vendorUri;
	}

	/**
	 * Get package vendor
	 *
	 * @return string
	 */
	public function getVendor()
	{
		return $this->vendor;
	}

	/**
	 * Set package vendor
	 *
	 * @param string $vendor
	 * @return void
	 */
	public function setVendor($vendor)
	{
		$this->vendor = $vendor;
	}

	/**
	 * Get package URL
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Set package URL
	 *
	 * @param string $url
	 * @return void
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * Get package icon URL
	 *
	 * @return string
	 */
	public function getIconUrl()
	{
		return $this->icon_url;
	}

	/**
	 * Set package icon URL
	 *
	 * @param string $iconUrl
	 * @return void
	 */
	public function setIconUrl($iconUrl)
	{
		$this->icon_url = $iconUrl;
	}

	/**
	 * Get package certification level
	 *
	 * @return string
	 */
	public function getCert()
	{
		return $this->cert;
	}

	/**
	 * Set package certification level
	 *
	 * @param string $cert
	 * @return void
	 */
	public function setCert($cert)
	{
		$this->cert = $cert;
	}

	/**
	 * Get package status
	 *
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Set package status
	 *
	 * @param string $status
	 * @return void
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}
}
