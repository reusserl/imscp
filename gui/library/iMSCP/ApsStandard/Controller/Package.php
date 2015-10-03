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

namespace iMSCP\ApsStandard\Controller;

use iMSCP\ApsStandard\Document;
use iMSCP\ApsStandard\Entity\Package as PackageEntity;
use iMSCP\ApsStandard\Entity\PackageCollection;
use iMSCP\ApsStandard\Entity\PackageDetails as PackageDetailsEntity;
use iMSCP\ApsStandard\Spider;

/**
 * Class Package
 * @package iMSCP\ApsStandard\Controller
 */
class Package extends ActionController
{
	/**
	 * Handle HTTP request
	 *
	 * @return void
	 */
	public function handleRequest()
	{
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': //
				if (!isset($_GET['id'])) {
					$this->indexAction();
				} else {
					$this->showDetailsAction(intval($_GET['id']));
				}
				break;
			case 'PUT':
				$this->changeStatusAction();
				break;
			case 'POST':
				$this->updateIndexAction();
		}

		$this->sendResponse(400, array('message' => tr('Bad request.')));
	}

	/**
	 * Lists all packages
	 *
	 * @void
	 */
	protected function indexAction()
	{
		try {
			$stmt = $this->db->query(sprintf('SELECT * FROM aps_packages WHERE status %s',
				// Show only unlocked packages to clients and all packages to administrators
				($this->identity->admin_type === 'admin') ? " IN('ok', 'disabled')" : " = 'ok'"
			));

			$collection = new PackageCollection();
			$collection->hydrate($stmt->fetchAll(\PDO::FETCH_ASSOC));
			$this->sendResponse(200, $collection);
		} catch (\Exception $e) {
			write_log(sprintf('Could not get package list: %s', $e->getMessage()), E_USER_ERROR);

			if ($this->identity->admin_type == 'admin') {
				$this->sendResponse(500, array('message' => tr('Could not get package list: %s', $e->getMessage())));
			} else {
				$this->sendResponse(500, array('message' => tr('Could not get package list. Please contact your reseller.')));
			}
		}
	}

	/**
	 * Show one package
	 *
	 * @param $packageId
	 */
	protected function showDetailsAction($packageId)
	{
		try {
			$stmt = $this->db->prepare('SELECT * FROM aps_packages WHERE id = ?');
			$stmt->execute(array($packageId));

			if ($stmt->rowCount()) {
				$pkgDetails = new PackageDetailsEntity();
				$pkgDetails->hydrate($stmt->fetch());

				// Retrieve missing metadata
				$pkgMetaFile = $this->getPackageMetadataDir() . '/' . $pkgDetails->getApsVersion() . '/' .
					$pkgDetails->getName() . '/APP-META.xml';

				if (file_exists($pkgMetaFile)) {
					$doc = new Document($pkgMetaFile);
					$pkgDetails->hydrate(array('description' => $doc->getXPathValue("//root:description")));
					$this->sendResponse(200, $pkgDetails);
				}

				write_log(sprintf("Could not find the %s package META file", $pkgMetaFile), E_USER_ERROR);
				throw new \RuntimeException(tr('Could not find the %s package META file', $pkgMetaFile));
			}

			$this->sendResponse(400, array('message' => tr('Bad request.')));
		} catch (\Exception $e) {
			write_log(sprintf('Could not get package details: %s', $e->getMessage()), E_USER_ERROR);

			if ($this->identity->admin_type == 'admin') {
				$this->sendResponse(500, array('message' => tr('Could not get package details: %s', $e->getMessage())));
			} else {
				$this->sendResponse(500, array('message' => tr('Could not get package details. Please contact your reseller.')));
			}
		}
	}

	/**
	 * Change (lock/unlock) package status
	 *
	 * @return void
	 */
	protected function changeStatusAction()
	{
		try {
			if ($this->identity->admin_type == 'admin') { // Only administrators can change package status (lock/unlock)
				$payload = @json_decode(@file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);

				if (is_array($payload)) {
					$package = new PackageEntity();
					$package->hydrate($payload);

					if (count($this->getValidator()->validate($package)) == 0) {
						$this->eventManager->dispatch('beforeApsPackageChangeStatus', array('package' => $package));

						$stmt = $this->db->prepare('UPDATE aps_packages SET status = ? WHERE id = ?');
						$stmt->execute(array($package->getStatus(), $package->getId()));

						if ($stmt->rowCount()) {
							$this->eventManager->dispatch('afterApsPackageChangeStatus', array('package' => $package));
							$this->sendResponse(204);
						}
					}
				}
			}

			$this->sendResponse(400, array('message' => tr('Bad request.')));
		} catch (\Exception $e) {
			write_log(sprintf('Could not change package status: %s', $e->getMessage()), E_USER_ERROR);
			$this->sendResponse(500, array('message' => tr('Could not change package status: %s', $e->getMessage())));
		}
	}

	/**
	 * Update package index by exploring APS standard repositories
	 *
	 * @return void
	 */
	protected function updateIndexAction()
	{
		try {
			if ($this->identity->admin_type == 'admin') {
				$this->eventManager->dispatch('beforeApsPackageUpdateIndex');
				$spider = new Spider();
				$spider->exploreCatalog();
				$this->eventManager->dispatch('afterApsPackageUpdateIndex');
				$this->sendResponse(200, array('message' => tr('Package index has been updated.')));
			}

			$this->sendResponse(400, array('message' => tr('Bad request')));
		} catch (\Exception $e) {
			write_log(sprintf('Could not update package index: %s', $e->getMessage()), E_USER_ERROR);
			$this->sendResponse(500, array('message' => tr('Could not update package index: %s', $e->getMessage())));
		}
	}
}
