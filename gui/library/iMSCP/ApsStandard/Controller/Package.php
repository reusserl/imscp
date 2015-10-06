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
use iMSCP\ApsStandard\Model\Package as PackageModel;
use iMSCP\ApsStandard\Model\PackageCollection;
use iMSCP\ApsStandard\Model\PackageDetails as PackageDetailsModel;
use iMSCP\ApsStandard\Spider;
use Zend_Session as Session;

/**
 * Class Package
 * @package iMSCP\ApsStandard\Controller
 */
class Package extends ControllerAbstract
{
	/**
	 * Handle HTTP request
	 *
	 * @return void
	 */
	public function handleRequest()
	{
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				if (!isset($_GET['id'])) {
					$this->index();
				} else {
					$this->showDetails(intval($_GET['id']));
				}
				break;
			case 'PUT':
				$this->changeStatus();
				break;
			case 'POST':
				$this->updateIndex();
		}

		$this->sendResponse(400, array('message' => tr('Bad request.')));
	}

	/**
	 * Lists all packages
	 *
	 * @void
	 */
	protected function index()
	{
		try {
			$pkgCollection = new PackageCollection();
			$stmt = $this->db->query(sprintf('SELECT * FROM aps_packages WHERE status %s',
				// Show only unlocked packages to clients and all packages to administrators
				($this->identity->admin_type === 'admin') ? " IN('ok', 'disabled')" : " = 'ok'"
			));
			$pkgCollection->hydrate($stmt->fetchAll(\PDO::FETCH_ASSOC));
			$this->sendResponse(200, $pkgCollection);
		} catch (\Exception $e) {
			write_log(sprintf('Could not get package list: %s', $e->getMessage()), E_USER_ERROR);

			if ($this->identity->admin_type === 'admin') {
				$this->sendResponse(500, array('message' => tr('Could not get package list: %s', $e->getMessage())));
			} else {
				$this->sendResponse(500, array('message' => tr('Could not get package list. Please contact your reseller.')));
			}
		}
	}

	/**
	 * Show package details
	 *
	 * @param $packageId
	 */
	protected function showDetails($packageId)
	{
		try {
			$stmt = $this->db->prepare(sprintf(
				'SELECT * FROM aps_packages WHERE id = ? AND status %s',
				// Client are not allowed to get details about locked packages
				($this->identity->admin_type === 'admin') ? " IN('ok', 'disabled')" : " = 'ok'"
			));
			$stmt->execute(array($packageId));

			if ($stmt->rowCount()) {
				$pkgDetails = new PackageDetailsModel();
				$pkgDetails->hydrate($stmt->fetch());

				// Retrieve missing data by parsing package metadata file
				$pkgMetaFile = $this->getPackageMetadataDir() . '/' . $pkgDetails->getApsVersion() . '/' .
					$pkgDetails->getName() . '/APP-META.xml';

				if (file_exists($pkgMetaFile) && filesize($pkgMetaFile) != 0) {
					$doc = new Document($pkgMetaFile);
					$pkgDetails->setDescription($doc->getXPathValue("//root:description"));
					$pkgDetails->setPackager(
						$doc->getXPathValue("//root:packager/root:name") ?:
							parse_url($doc->getXPathValue("//root:package-homepage"), PHP_URL_HOST) ?: tr('Unknown')
					);
					$this->sendResponse(200, $pkgDetails);
				}

				throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $pkgMetaFile));
			}

			$this->sendResponse(400, array('message' => tr('Bad request.')));
		} catch (\Exception $e) {
			write_log(sprintf('Could not get package details: %s', $e->getMessage()), E_USER_ERROR);

			if ($this->identity->admin_type === 'admin') {
				$this->sendResponse(500, array('message' => tr('Could not get package details: %s', $e->getMessage())));
			} else {
				$this->sendResponse(500, array('message' => tr('Could not get package details. Please contact your reseller.')));
			}
		}
	}

	/**
	 * Change package status
	 *
	 * @return void
	 */
	protected function changeStatus()
	{
		try {
			if ($this->identity->admin_type === 'admin') { // Only administrators can change package status
				$payload = @json_decode(@file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);

				if (is_array($payload)) {
					$pkg = new PackageModel();
					$pkg->hydrate($payload);

					if (count($this->getValidator()->validate($pkg)) == 0) {
						$this->eventManager->dispatch('beforeApsPackageChangeStatus', array('package' => $pkg));

						$stmt = $this->db->prepare('UPDATE aps_packages SET status = ? WHERE id = ?');
						$stmt->execute(array($pkg->getStatus(), $pkg->getId()));

						if ($stmt->rowCount()) {
							$this->eventManager->dispatch('afterApsPackageChangeStatus', array('package' => $pkg));
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
	protected function updateIndex()
	{
		try {
			if ($this->identity->admin_type == 'admin') {
				// We need close session to prevent connection blocking from same host
				// See for a better explaination
				Session::writeClose();
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
