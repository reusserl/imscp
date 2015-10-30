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
use iMSCP_Authentication as Auth;
use iMSCP\Events\EventManagerAwareInterface;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events_Manager_Interface as EventManagerInterface;
use iMSCP_Registry as Registry;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ApsAbstractService
 * @package iMSCP\ApsStandard\Service
 */
abstract class ApsAbstractService implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
	/**
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/***
	 * @var Auth
	 */
	protected $auth;

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
		// '2.0' Not supported yet
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
	 * @throws \iMSCP_Exception
	 * @param EntityManager $entityManager
	 * @param Auth $auth
	 */
	public function __construct(EntityManager $entityManager, Auth $auth)
	{
		$this->entityManager = $entityManager;
		$this->auth = $auth;
		$config = Registry::get('config');
		$this->setMetadataDir($config['CACHE_DATA_DIR'] . '/aps_standard/metadata');
		$this->setPackageDir($config['CACHE_DATA_DIR'] . '/aps_standard/packages');
		$this->init();
	}

	/**
	 * Initialize service (Allow child classes to not override constructor)
	 *
	 * @return void
	 */
	public function init()
	{
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
	 * {@inheritdoc}
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getServiceLocator()
	{
		return $this->serviceLocator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getValidator()
	{
		return $this->getServiceLocator()->get('Validator');
	}

	/**
	 * Get entity manager
	 *
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}

	/**
	 * Get authentication object
	 *
	 * @return Auth
	 */
	protected function getAuth()
	{
		return $this->auth;
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
	public function getMetadataDir()
	{
		return $this->packageMetadataDir;
	}

	/**
	 * Set package metadata directory
	 *
	 * @param string $packageMetadataDir
	 */
	public function setMetadataDir($packageMetadataDir)
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
