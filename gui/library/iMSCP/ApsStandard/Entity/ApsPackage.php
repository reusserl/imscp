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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation AS JMS;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use iMSCP\Validate\ValidationInterface;

/**
 * ApsPackage
 *
 * @ORM\Table(name="aps_package")
 * @ORM\Entity
 * @JMS\AccessType("public_method")
 */
class ApsPackage implements ValidationInterface
{
	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 * @JMS\Type("integer")
	 * @JMS\AccessType("property")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $name;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="summary", type="text", length=65535, nullable=false)
	 * @JMS\Type("string")
	 */
	private $summary;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="version", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $version;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="release", type="integer", nullable=false)
	 * @JMS\Type("integer")
	 */
	private $release;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="aps_version", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $apsVersion;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="category", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $category;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="vendor", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $vendor;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="vendor_uri", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $vendorUri;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="url", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $url;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="icon_url", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $iconUrl;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="cert", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $cert;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="status", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 */
	private $status;

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 * @return ApsPackage
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set summary
	 *
	 * @param string $summary
	 * @return ApsPackage
	 */
	public function setSummary($summary)
	{
		$this->summary = $summary;

		return $this;
	}

	/**
	 * Get summary
	 *
	 * @return string
	 */
	public function getSummary()
	{
		return $this->summary;
	}

	/**
	 * Set version
	 *
	 * @param string $version
	 * @return ApsPackage
	 */
	public function setVersion($version)
	{
		$this->version = $version;

		return $this;
	}

	/**
	 * Get version
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Set release
	 *
	 * @param integer $release
	 * @return ApsPackage
	 */
	public function setRelease($release)
	{
		$this->release = $release;

		return $this;
	}

	/**
	 * Get release
	 *
	 * @return integer
	 */
	public function getRelease()
	{
		return $this->release;
	}

	/**
	 * Set apsVersion
	 *
	 * @param string $apsVersion
	 * @return ApsPackage
	 */
	public function setApsVersion($apsVersion)
	{
		$this->apsVersion = $apsVersion;

		return $this;
	}

	/**
	 * Get apsVersion
	 *
	 * @return string
	 */
	public function getApsVersion()
	{
		return $this->apsVersion;
	}

	/**
	 * Set category
	 *
	 * @param string $category
	 * @return ApsPackage
	 */
	public function setCategory($category)
	{
		$this->category = $category;

		return $this;
	}

	/**
	 * Get category
	 *
	 * @return string
	 */
	public function getCategory()
	{
		return $this->category;
	}

	/**
	 * Set vendor
	 *
	 * @param string $vendor
	 * @return ApsPackage
	 */
	public function setVendor($vendor)
	{
		$this->vendor = $vendor;

		return $this;
	}

	/**
	 * Get vendor
	 *
	 * @return string
	 */
	public function getVendor()
	{
		return $this->vendor;
	}

	/**
	 * Set vendorUri
	 *
	 * @param string $vendorUri
	 * @return ApsPackage
	 */
	public function setVendorUri($vendorUri)
	{
		$this->vendorUri = $vendorUri;

		return $this;
	}

	/**
	 * Get vendorUri
	 *
	 * @return string
	 */
	public function getVendorUri()
	{
		return $this->vendorUri;
	}

	/**
	 * Set url
	 *
	 * @param string $url
	 * @return ApsPackage
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * Get url
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Set iconUrl
	 *
	 * @param string $iconUrl
	 * @return ApsPackage
	 */
	public function setIconUrl($iconUrl)
	{
		$this->iconUrl = $iconUrl;

		return $this;
	}

	/**
	 * Get iconUrl
	 *
	 * @return string
	 */
	public function getIconUrl()
	{
		return $this->iconUrl;
	}

	/**
	 * Set cert
	 *
	 * @param string $cert
	 * @return ApsPackage
	 */
	public function setCert($cert)
	{
		$this->cert = $cert;

		return $this;
	}

	/**
	 * Get cert
	 *
	 * @return string
	 */
	public function getCert()
	{
		return $this->cert;
	}

	/**
	 * Set status
	 *
	 * @param string $status
	 * @return ApsPackage
	 */
	public function setStatus($status)
	{
		$this->status = $status;

		return $this;
	}

	/**
	 * Get status
	 *
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Load validation metadata
	 *
	 * @param ClassMetadata $metadata
	 * @return void
	 */
	public static function loadValidationMetadata(ClassMetadata $metadata)
	{
		// Right now, only the status field is mutable. Thus, we process validation only for that field.
		$metadata->addPropertyConstraint('status', new Assert\Choice(array('choices' => array('ok', 'disabled'))));
	}
}
