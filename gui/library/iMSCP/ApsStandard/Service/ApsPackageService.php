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
use iMSCP\ApsStandard\ApsDocument;
use iMSCP\ApsStandard\Entity\ApsPackageDetails;
use iMSCP_Authentication as Authentication;
use iMSCP\ApsStandard\Entity\ApsPackages;
use iMSCP\ApsStandard\Model\PackageCollection;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend_Session as SessionHandler;

/**
 * Class ApsPackageService
 * @package iMSCP\ApsStandard\Service
 */
class ApsPackageService extends AbstractApsService implements ServiceLocatorAwareInterface
{
	/***
	 * @var Authentication
	 */
	protected $authentication;

	/** @var  ServiceLocatorInterface */
	protected $serviceLocator;

	/**
	 * Constructor
	 *
	 * @param EntityManager $entityManager
	 * @param Authentication $auth
	 */
	public function __construct(EntityManager $entityManager, Authentication $auth)
	{
		parent::__construct($entityManager);
		$this->authentication = $auth;
	}

	/**
	 * Set service locator
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 * Get service locator
	 *
	 * @return ServiceLocatorInterface
	 */
	public function getServiceLocator()
	{
		return $this->serviceLocator;
	}

	/**
	 * Find all packages
	 *
	 * @return PackageCollection[]
	 */
	public function getPackages()
	{
		$packageCollection = $this->getEntityManager()->getRepository('ApsStandard:ApsPackages')->findBy(
			array('status' => ($this->getUserIdentity()->admin_type === 'admin') ? array('ok', 'disabled') : 'ok')
		);
		$this->getEventManager()->dispatch('onFindApsPackages', array('packages' => $packageCollection));
		return $packageCollection;
	}

	/**
	 * Find package details
	 *
	 * @param int $id Package identity
	 * @return ApsPackageDetails|null
	 */
	public function getPackageDetails($id)
	{
		$package = $this->getEntityManager()->getRepository('ApsStandard:ApsPackages')->find($id);
		if (!$package) {
			return null;
		}

		// Retrieve missing data by parsing package metadata file
		$packageMetaFile = $this->getPackageMetadataDir() . '/' . $package->getApsVersion() . '/' .
			$package->getName() . '/APP-META.xml';

		if (!file_exists($packageMetaFile) || filesize($packageMetaFile) == 0) {
			throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $packageMetaFile));
		}

		$doc = new ApsDocument($packageMetaFile);
		$packageDetails = new ApsPackageDetails();
		$packageDetails->setDescription($doc->getXPathValue("//root:description"));
		$packageDetails->setPackager($doc->getXPathValue("//root:packager/root:name") ?:
			parse_url($doc->getXPathValue("//root:package-homepage"), PHP_URL_HOST) ?: tr('Unknown')
		);
		$this->eventManager->dispatch('onFindApsPackageDetails', array('package_details' => $packageDetails));
		return $packageDetails;
	}

	/**
	 * Update package status
	 *
	 * @param int $packageId Package identity
	 * @param string $status New package status
	 * @return ApsPackages|null
	 */
	public function updatePackageStatus($packageId, $status)
	{
		$package = $this->getEntityManager()->getRepository('ApsStandard:ApsPackages')->find($packageId);
		if (!$package) {
			return null;
		}

		$package->setStatus($status);
		$this->getEventManager()->dispatch('onUpdateApsPackageStatus', array('package' => $package));
		$this->getEntityManager()->flush($package);
		return $package;
	}

	/**
	 * Update package index
	 *
	 * @throws \Exception
	 */
	public function updatePackageIndex()
	{
		SessionHandler::writeClose();
		$spider = $this->getServiceLocator()->get('ApsSpiderService');
		$spider->exploreCatalog();
		$this->getEventManager()->dispatch('onUpdateApsPackageIndex');
	}

	/**
	 * Get user identity
	 *
	 * @return null|\stdClass
	 */
	protected function getUserIdentity()
	{
		return $this->authentication->getIdentity();
	}
}
