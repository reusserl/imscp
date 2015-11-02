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

namespace iMSCP\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Admin
 *
 * @package iMSCP\Entity
 * @ORM\Table(
 *   name="admin", uniqueConstraints={@ORM\UniqueConstraint(name="admin_name", columns={"admin_name"})},
 *   indexes={@ORM\Index(name="created_by", columns={"created_by"})},
 *   options={"collate"="utf8_unicode_ci", "charset"="utf8", "engine"="InnoDB"}
 * )
 * @ORM\Entity
 */
class Admin
{
	/**
	 * @var integer
	 *
	 * @ORM\Column(name="admin_id", type="integer", nullable=false, options={"unsigned":true})
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $adminId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="admin_name", type="string", length=200, nullable=true)
	 */
	private $adminName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="admin_pass", type="string", length=200, nullable=true)
	 */
	private $adminPass;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="admin_type", type="string", length=10, nullable=true)
	 */
	private $adminType;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="admin_sys_name", type="string", length=16, nullable=true)
	 */
	private $adminSysName;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="admin_sys_uid", type="integer", nullable=false)
	 */
	private $adminSysUid;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="admin_sys_gname", type="string", length=32, nullable=true)
	 */
	private $adminSysGname;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="admin_sys_gid", type="integer", nullable=false)
	 */
	private $adminSysGid;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="domain_created", type="integer", nullable=false)
	 */
	private $domainCreated;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="customer_id", type="string", length=200, nullable=true)
	 */
	private $customerId;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="created_by", type="integer", nullable=true)
	 */
	private $createdBy;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fname", type="string", length=200, nullable=true)
	 */
	private $fname;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="lname", type="string", length=200, nullable=true)
	 */
	private $lname;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="gender", type="string", length=1, nullable=true)
	 */
	private $gender;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="firm", type="string", length=200, nullable=true)
	 */
	private $firm;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="zip", type="string", length=10, nullable=true)
	 */
	private $zip;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="city", type="string", length=200, nullable=true)
	 */
	private $city;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="state", type="string", length=200, nullable=true)
	 */
	private $state;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="country", type="string", length=200, nullable=true)
	 */
	private $country;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="email", type="string", length=200, nullable=true)
	 */
	private $email;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="phone", type="string", length=200, nullable=true)
	 */
	private $phone;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fax", type="string", length=200, nullable=true)
	 */
	private $fax;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="street1", type="string", length=200, nullable=true)
	 */
	private $street1;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="street2", type="string", length=200, nullable=true)
	 */
	private $street2;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="uniqkey", type="string", length=255, nullable=true)
	 */
	private $uniqkey;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="uniqkey_time", type="datetime", nullable=true)
	 */
	private $uniqkeyTime;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="admin_status", type="string", length=255, nullable=false)
	 */
	private $adminStatus;

	/**
	 * Get adminId
	 *
	 * @return integer
	 */
	public function getAdminId()
	{
		return $this->adminId;
	}

	/**
	 * Set adminName
	 *
	 * @param string $adminName
	 * @return Admin
	 */
	public function setAdminName($adminName)
	{
		$this->adminName = $adminName;
		return $this;
	}

	/**
	 * Get adminName
	 *
	 * @return string
	 */
	public function getAdminName()
	{
		return $this->adminName;
	}

	/**
	 * Set adminPass
	 *
	 * @param string $adminPass
	 * @return Admin
	 */
	public function setAdminPass($adminPass)
	{
		$this->adminPass = $adminPass;
		return $this;
	}

	/**
	 * Get adminPass
	 *
	 * @return string
	 */
	public function getAdminPass()
	{
		return $this->adminPass;
	}

	/**
	 * Set adminType
	 *
	 * @param string $adminType
	 * @return Admin
	 */
	public function setAdminType($adminType)
	{
		$this->adminType = $adminType;
		return $this;
	}

	/**
	 * Get adminType
	 *
	 * @return string
	 */
	public function getAdminType()
	{
		return $this->adminType;
	}

	/**
	 * Set adminSysName
	 *
	 * @param string $adminSysName
	 * @return Admin
	 */
	public function setAdminSysName($adminSysName)
	{
		$this->adminSysName = $adminSysName;
		return $this;
	}

	/**
	 * Get adminSysName
	 *
	 * @return string
	 */
	public function getAdminSysName()
	{
		return $this->adminSysName;
	}

	/**
	 * Set adminSysUid
	 *
	 * @param integer $adminSysUid
	 * @return Admin
	 */
	public function setAdminSysUid($adminSysUid)
	{
		$this->adminSysUid = $adminSysUid;
		return $this;
	}

	/**
	 * Get adminSysUid
	 *
	 * @return integer
	 */
	public function getAdminSysUid()
	{
		return $this->adminSysUid;
	}

	/**
	 * Set adminSysGname
	 *
	 * @param string $adminSysGname
	 * @return Admin
	 */
	public function setAdminSysGname($adminSysGname)
	{
		$this->adminSysGname = $adminSysGname;
		return $this;
	}

	/**
	 * Get adminSysGname
	 *
	 * @return string
	 */
	public function getAdminSysGname()
	{
		return $this->adminSysGname;
	}

	/**
	 * Set adminSysGid
	 *
	 * @param integer $adminSysGid
	 * @return Admin
	 */
	public function setAdminSysGid($adminSysGid)
	{
		$this->adminSysGid = $adminSysGid;
		return $this;
	}

	/**
	 * Get adminSysGid
	 *
	 * @return integer
	 */
	public function getAdminSysGid()
	{
		return $this->adminSysGid;
	}

	/**
	 * Set domainCreated
	 *
	 * @param integer $domainCreated
	 * @return Admin
	 */
	public function setDomainCreated($domainCreated)
	{
		$this->domainCreated = $domainCreated;
		return $this;
	}

	/**
	 * Get domainCreated
	 *
	 * @return integer
	 */
	public function getDomainCreated()
	{
		return $this->domainCreated;
	}

	/**
	 * Set customerId
	 *
	 * @param string $customerId
	 * @return Admin
	 */
	public function setCustomerId($customerId)
	{
		$this->customerId = $customerId;
		return $this;
	}

	/**
	 * Get customerId
	 *
	 * @return string
	 */
	public function getCustomerId()
	{
		return $this->customerId;
	}

	/**
	 * Set createdBy
	 *
	 * @param integer $createdBy
	 * @return Admin
	 */
	public function setCreatedBy($createdBy)
	{
		$this->createdBy = $createdBy;
		return $this;
	}

	/**
	 * Get createdBy
	 *
	 * @return integer
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * Set fname
	 *
	 * @param string $fname
	 * @return Admin
	 */
	public function setFname($fname)
	{
		$this->fname = $fname;
		return $this;
	}

	/**
	 * Get fname
	 *
	 * @return string
	 */
	public function getFname()
	{
		return $this->fname;
	}

	/**
	 * Set lname
	 *
	 * @param string $lname
	 * @return Admin
	 */
	public function setLname($lname)
	{
		$this->lname = $lname;
		return $this;
	}

	/**
	 * Get lname
	 *
	 * @return string
	 */
	public function getLname()
	{
		return $this->lname;
	}

	/**
	 * Set gender
	 *
	 * @param string $gender
	 * @return Admin
	 */
	public function setGender($gender)
	{
		$this->gender = $gender;
		return $this;
	}

	/**
	 * Get gender
	 *
	 * @return string
	 */
	public function getGender()
	{
		return $this->gender;
	}

	/**
	 * Set firm
	 *
	 * @param string $firm
	 * @return Admin
	 */
	public function setFirm($firm)
	{
		$this->firm = $firm;
		return $this;
	}

	/**
	 * Get firm
	 *
	 * @return string
	 */
	public function getFirm()
	{
		return $this->firm;
	}

	/**
	 * Set zip
	 *
	 * @param string $zip
	 * @return Admin
	 */
	public function setZip($zip)
	{
		$this->zip = $zip;
		return $this;
	}

	/**
	 * Get zip
	 *
	 * @return string
	 */
	public function getZip()
	{
		return $this->zip;
	}

	/**
	 * Set city
	 *
	 * @param string $city
	 * @return Admin
	 */
	public function setCity($city)
	{
		$this->city = $city;
		return $this;
	}

	/**
	 * Get city
	 *
	 * @return string
	 */
	public function getCity()
	{
		return $this->city;
	}

	/**
	 * Set state
	 *
	 * @param string $state
	 * @return Admin
	 */
	public function setState($state)
	{
		$this->state = $state;
		return $this;
	}

	/**
	 * Get state
	 *
	 * @return string
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Set country
	 *
	 * @param string $country
	 * @return Admin
	 */
	public function setCountry($country)
	{
		$this->country = $country;
		return $this;
	}

	/**
	 * Get country
	 *
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * Set email
	 *
	 * @param string $email
	 * @return Admin
	 */
	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}

	/**
	 * Get email
	 *
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Set phone
	 *
	 * @param string $phone
	 * @return Admin
	 */
	public function setPhone($phone)
	{
		$this->phone = $phone;
		return $this;
	}

	/**
	 * Get phone
	 *
	 * @return string
	 */
	public function getPhone()
	{
		return $this->phone;
	}

	/**
	 * Set fax
	 *
	 * @param string $fax
	 * @return Admin
	 */
	public function setFax($fax)
	{
		$this->fax = $fax;
		return $this;
	}

	/**
	 * Get fax
	 *
	 * @return string
	 */
	public function getFax()
	{
		return $this->fax;
	}

	/**
	 * Set street1
	 *
	 * @param string $street1
	 * @return Admin
	 */
	public function setStreet1($street1)
	{
		$this->street1 = $street1;
		return $this;
	}

	/**
	 * Get street1
	 *
	 * @return string
	 */
	public function getStreet1()
	{
		return $this->street1;
	}

	/**
	 * Set street2
	 *
	 * @param string $street2
	 * @return Admin
	 */
	public function setStreet2($street2)
	{
		$this->street2 = $street2;
		return $this;
	}

	/**
	 * Get street2
	 *
	 * @return string
	 */
	public function getStreet2()
	{
		return $this->street2;
	}

	/**
	 * Set uniqkey
	 *
	 * @param string $uniqkey
	 * @return Admin
	 */
	public function setUniqkey($uniqkey)
	{
		$this->uniqkey = $uniqkey;
		return $this;
	}

	/**
	 * Get uniqkey
	 *
	 * @return string
	 */
	public function getUniqkey()
	{
		return $this->uniqkey;
	}

	/**
	 * Set uniqkeyTime
	 *
	 * @param \DateTime $uniqkeyTime
	 * @return Admin
	 */
	public function setUniqkeyTime($uniqkeyTime)
	{
		$this->uniqkeyTime = $uniqkeyTime;
		return $this;
	}

	/**
	 * Get uniqkeyTime
	 *
	 * @return \DateTime
	 */
	public function getUniqkeyTime()
	{
		return $this->uniqkeyTime;
	}

	/**
	 * Set adminStatus
	 *
	 * @param string $adminStatus
	 * @return Admin
	 */
	public function setAdminStatus($adminStatus)
	{
		$this->adminStatus = $adminStatus;
		return $this;
	}

	/**
	 * Get adminStatus
	 *
	 * @return string
	 */
	public function getAdminStatus()
	{
		return $this->adminStatus;
	}
}
