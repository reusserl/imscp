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

/**
 * Class ApsInstance
 *
 * @package iMSCP\ApsStandard\Entity
 * @ORM\Table(name="aps_instance", indexes={@ORM\Index(name="pid", columns={"pid"}), @ORM\Index(name="uid", columns={"uid"})})
 * @ORM\Entity
 */
class ApsInstance
{
	/**
	 * @var integer
	 * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned":true})
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(name="settings", type="json_array", length=65535, nullable=false)
	 */
	private $settings;

	/**
	 * @var string
	 * @ORM\Column(name="status", type="string", length=255, nullable=false)
	 */
	private $status;

	/**
	 * @var \iMSCP\ApsStandard\Entity\ApsPackage
	 * @ORM\ManyToOne(targetEntity="iMSCP\ApsStandard\Entity\ApsPackage")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="SET NULL")
	 * })
	 */
	private $package;

	/**
	 * @var \iMSCP\Entity\Admin
	 * @ORM\ManyToOne(targetEntity="iMSCP\Entity\Admin")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="uid", referencedColumnName="admin_id", nullable=false, onDelete="CASCADE")
	 * })
	 */
	private $owner;

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
	 * Set settings
	 *
	 * @param array $settings
	 * @return ApsInstance
	 */
	public function setSettings(array $settings)
	{
		$this->settings = $settings;
		return $this;
	}

	/**
	 * Get settings
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Set status
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
	 * Get status
	 *
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Set pid
	 *
	 * @param \iMSCP\ApsStandard\Entity\ApsPackage|null $package
	 * @return ApsInstance
	 */
	public function setPackage(ApsPackage $package = null)
	{
		$this->package = $package;
		return $this;
	}

	/**
	 * Get package
	 *
	 * @return \iMSCP\ApsStandard\Entity\ApsPackage
	 */
	public function getPid()
	{
		return $this->package;
	}

	/**
	 * Set owner
	 *
	 * @param \iMSCP\ApsStandard\Entity\Admin $owner
	 * @return ApsInstance
	 */
	public function setUid(Admin $owner = null)
	{
		$this->owner = $owner;
		return $this;
	}

	/**
	 * Get owner
	 *
	 * @return \iMSCP\ApsStandard\Entity\Admin
	 */
	public function getOwner()
	{
		return $this->owner;
	}
}
