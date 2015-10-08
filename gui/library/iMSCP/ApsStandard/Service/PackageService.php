<?php

namespace iMSCP\ApsStandard\Service;

use iMSCP\ApsStandard\Document;
use iMSCP\ApsStandard\Model\Package;
use iMSCP\ApsStandard\Model\PackageCollection;
use iMSCP\ApsStandard\Model\PackageDetails;
use iMSCP\ApsStandard\Spider;
use Zend_Session as SessionHandler;

/**
 * Class PackageService
 * @package iMSCP\ApsStandard\Service
 */
class PackageService extends ServiceAbstract
{
	/**
	 * Find all packages
	 *
	 * @return PackageCollection
	 */
	public function findAllPackages()
	{
		$packageCollection = new PackageCollection();
		$this->eventManager->dispatch('beforeFindAllApsPackages', array('packageCollection' => $packageCollection));
		$stmt = $this->db->query(sprintf('SELECT * FROM aps_packages WHERE status %s',
			// Show only unlocked packages to clients and all packages to administrators
			($this->identity->admin_type === 'admin') ? " IN('ok', 'disabled')" : " = 'ok'"
		));
		$packageCollection->hydrate($stmt->fetchAll(\PDO::FETCH_ASSOC));
		$this->eventManager->dispatch('afterFindAllApsPackages', array('packageCollection' => $packageCollection));
		return $packageCollection;
	}

	/**
	 * Find package
	 *
	 * @param int $id Package identity
	 * @return Package|null
	 */
	public function findPackage($id)
	{
		$package = new Package();
		$this->eventManager->dispatch('beforeFindApsPackage', array('package' => $package));
		$stmt = $this->db->query(
			'SELECT * FROM aps_packages WHERE id = ? AND status %s', array(
			$id,
			// Clients are not allowed to see locked packages
			($this->identity->admin_type === 'admin') ? " IN('ok', 'disabled')" : " = 'ok'"
		));

		if (!$stmt->rowCount()) {
			return null;
		}

		$package->hydrate($stmt->fetch(\PDO::FETCH_ASSOC));
		$this->eventManager->dispatch('afterFindApsPackage', array('package' => $package));
		return $package;
	}

	/**
	 * Find package details
	 *
	 * @param int $id Package identity
	 * @return PackageDetails|null
	 */
	public function findPackageDetails($id)
	{
		$packageDetails = new PackageDetails();
		$this->eventManager->dispatch('beforeFindApsPackageDetails', array('package' => $packageDetails));
		$stmt = $this->db->prepare(sprintf(
			'SELECT * FROM aps_packages WHERE id = ? AND status %s',
			// Clients are not allowed to see locked packages
			($this->identity->admin_type === 'admin') ? " IN('ok', 'disabled')" : " = 'ok'"
		));
		$stmt->execute(array($id));

		if (!$stmt->rowCount()) {
			return null;
		}

		$packageDetails->hydrate($stmt->fetch());

		// Retrieve missing data by parsing package metadata file
		$packageMetaFile = $this->getPackageMetadataDir() . '/' . $packageDetails->getApsVersion() . '/' .
			$packageDetails->getName() . '/APP-META.xml';

		if (!file_exists($packageMetaFile) || filesize($packageMetaFile) == 0) {
			throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $packageMetaFile));
		}

		$doc = new Document($packageMetaFile);
		$packageDetails->setDescription($doc->getXPathValue("//root:description"));
		$packageDetails->setPackager(
			$doc->getXPathValue("//root:packager/root:name") ?:
				parse_url($doc->getXPathValue("//root:package-homepage"), PHP_URL_HOST) ?: tr('Unknown')
		);

		$this->eventManager->dispatch('afterFindApsPackageDetails', array('package' => $packageDetails));
		return $packageDetails;
	}

	/**
	 * Update package status
	 *
	 * @param Package $package
	 * @return Package|null
	 */
	public function updatePackageStatus(Package $package)
	{
		$this->eventManager->dispatch('beforeUpdateApsPackageStatus', array('package' => $package));
		$stmt = $this->db->prepare('UPDATE aps_packages SET status = ? WHERE id = ?');
		$stmt->execute(array($package->getStatus(), $package->getId()));

		if (!$stmt->rowCount()) {
			return null;
		}

		$this->eventManager->dispatch('afterUpdateApsPackageStatus', array('package' => $package));
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
		$this->eventManager->dispatch('beforeUpdateApsPackageIndex');
		$spider = new Spider();
		$spider->exploreCatalog();
		$this->eventManager->dispatch('afterUpdateApsPackageIndex');
	}
}
