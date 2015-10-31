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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use iMSCP\Entity\Admin;
use JMS\Serializer\Annotation AS JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ApsInstance
 *
 * @package iMSCP\ApsStandard\Entity
 * @ORM\Table(
 *   name="aps_instance",
 *   indexes={@ORM\Index(columns={"package_id"}), @ORM\Index(columns={"owner_id"}), @ORM\Index(columns={"status"})},
 *   options={"collate"="utf8_unicode_ci", "charset"="utf8", "engine"="InnoDB"}
 * )
 * @ORM\Entity
 * @JMS\AccessType("public_method")
 */
class ApsInstance
{
	/**
	 * @var integer
	 * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned":true})
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 * @JMS\Type("integer")
	 * @JMS\AccessType("property")
	 */
	private $id;

	/**
	 * @var \iMSCP\ApsStandard\Entity\ApsPackage
	 * @ORM\ManyToOne(targetEntity="iMSCP\ApsStandard\Entity\ApsPackage")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="SET NULL")
	 * })
	 * @Assert\Valid()
	 */
	private $package;

	/**
	 * @var \iMSCP\Entity\Admin
	 * @ORM\ManyToOne(targetEntity="iMSCP\Entity\Admin")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="owner_id", referencedColumnName="admin_id", nullable=false, onDelete="CASCADE")
	 * })
	 * @Assert\Valid()
	 */
	private $owner;

	/**
	 * @var array
	 * @ORM\OneToMany(targetEntity="iMSCP\ApsStandard\Entity\ApsInstanceSetting", mappedBy="instance", cascade={"persist"})
	 */
	private $settings;

	/**
	 * @var string
	 * @ORM\Column(name="status", type="string", length=255, nullable=false)
	 * @JMS\Type("string")
	 * @Assert\Choice(choices = {"ok", "toadd", "tochange", "todelete"}, message = "Invalid status.")
	 */
	private $status;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->settings = new ArrayCollection();
	}

	/**
	 * Get instance identifier
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set package that belong to this instance
	 *
	 * @param ApsPackage|null $package
	 * @return ApsInstance
	 */
	public function setPackage(ApsPackage $package = null)
	{
		$this->package = $package;
		return $this;
	}

	/**
	 * Get package that belongs to this instance
	 *
	 * @return ApsPackage
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * Set owner of this instance
	 *
	 * @param Admin $owner
	 * @return ApsInstance
	 */
	public function setOwner(Admin $owner = null)
	{
		$this->owner = $owner;
		return $this;
	}

	/**
	 * Get owner of this instance
	 *
	 * @return Admin
	 */
	public function getOwner()
	{
		return $this->owner;
	}

	/**
	 * Get settings that belongs to this instance
	 *
	 * @return ArrayCollection[ApsInstanceSetting]
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Set settings that belongs to this instance
	 *
	 * @param ApsInstanceSetting[] $settings
	 * @return $this
	 */
	public function setSettings(array $settings)
	{
		foreach($settings as $setting) {
			$this->addSetting($setting);
		}

		return $this;
	}

	/**
	 * Add the given setting
	 *
	 * @param ApsInstanceSetting $setting
	 * @return $this
	 */
	public function addSetting(ApsInstanceSetting $setting)
	{
		$this->settings[] = $setting;
		$setting->setInstance($this);
		return $this;
	}

	/**
	 * Set instance status
	 *
	 * @param string $status
	 * @return ApsInstance
	 */
	public function setStatus($status)
	{
		$this->status = $status;
		return $this;
	}

	/**
	 * Get instance status
	 *
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}
}
