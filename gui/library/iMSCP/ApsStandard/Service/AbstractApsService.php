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

namespace iMSCP\ApsStandard\Service;

use Doctrine\ORM\EntityManager;
use iMSCP\Events\EventManagerAwareInterface;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events_Manager_Interface as EventManagerInterface;
use iMSCP_Registry as Registry;

/**
 * Class AbstractApsStandardService
 * @package iMSCP\ApsStandard\Service
 */
abstract class AbstractApsService implements EventManagerAwareInterface
{
	/**
	 * @var \stdClass $identity User identity
	 */
	protected $identity;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

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
	 *
	 * @param EntityManager $entityManager
	 * @throws \iMSCP_Exception
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
		$config = Registry::get('config');
		$this->setPackageMetadataDir($config['CACHE_DATA_DIR'] . '/aps_standard/metadata');
		$this->setPackageDir($config['CACHE_DATA_DIR'] . '/aps_standard/packages');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEventManager()
	{
		if (null === $this->eventManager) {
			$this->eventManager = $this->eventManager = EventManager::getInstance();
		}

		return $this->eventManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setEventManager(EventManagerInterface $eventManager)
	{
		$this->eventManager = $eventManager;
	}

	/**
	 * Get entity manager
	 *
	 * @return EntityManager
	 * @throws \iMSCP_Exception_Database
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
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
