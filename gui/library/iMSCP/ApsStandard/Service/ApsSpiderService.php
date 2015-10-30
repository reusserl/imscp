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

use iMSCP\ApsStandard\ApsDocument;
use iMSCP\ApsStandard\Entity\ApsPackage;
use iMSCP_Registry as Registry;

/**
 * Class ApsSpiderService
 * @package iMSCP\ApsStandard\Service
 */
class ApsSpiderService extends ApsAbstractService
{
	/**
	 * @var ApsPackage[][] packages (grouped by repository)
	 */
	protected $packages = array();

	/**
	 * @var array List of unlocked packages
	 */
	protected $unlockedPackages = array();

	/**
	 * @var resource Lock file
	 */
	protected $lockFile;

	/**
	 * Explore APS standard catalog
	 *
	 * Return void
	 */
	public function exploreCatalog()
	{
		try {
			$this->checkRequirements();
			$this->setupEnvironment();

			// Retrieves list of known packages
			/** @var ApsPackage[] $packages */
			$packages = $this->getEntityManager()->getRepository('Aps:ApsPackage')->findBy(array(
				'status' => array('locked', 'unlocked')
			));

			if (!empty($packages)) {
				foreach ($packages as $package) {
					$this->packages[$package->getApsVersion()][$package->getName()] = $package;

					if ($package->getStatus() == 'unlocked') {
						$this->unlockedPackages[] = $package->getName();
					}
				}
			}

			$serviceUrl = $this->getServiceURL();
			$systemIndex = new ApsDocument($serviceUrl, 'html');

			// Parse system index to retrieve list of available repositories
			// See: https://doc.apsstandard.org/2.1/portal/cat/browsing/#retrieving-repository-index
			$repositories = $systemIndex->getXPathValue("//a[@class='repository']/@href", null, false);

			foreach ($repositories as $repo) {
				$repoUrl = $repo->nodeValue;
				$repoId = rtrim($repoUrl, '/');

				// Explores supported APS standard repositories only
				if (in_array($repoId, $this->supportedRepositories)) {
					// Discover repository feed
					// See: https://doc.apsstandard.org/2.1/portal/cat/browsing/#discovering-repository-feed
					$repoIndex = new ApsDocument($serviceUrl . '/' . $repoUrl, 'html');
					$repoFeedUrl = $repoIndex->getXPathValue("//a[@id='feedLink']/@href");
					unset($repoIndex);

					if ($repoFeedUrl != '') { // Ignore invalid repository entry
						// Get list of known packages
						$knownPackages = isset($this->packages[$repoId]) ? $this->packages[$repoId] : array();

						// Parse the repository feed by chunk of 100 entries (we fetch only latest package versions)
						// See: https://doc.apsstandard.org/2.1/portal/cat/search/#search-description-arguments
						$repoFeed = new ApsDocument(
							$serviceUrl . str_replace('../', '/', $repoFeedUrl) . '?pageSize=100&latest=1'
						);
						$this->parseRepositoryFeedPage($repoFeed, $repoId, $knownPackages);
						while ($repoFeedUrl = $repoFeed->getXPathValue("root:link[@rel='next']/@href")) {
							$repoFeed = new ApsDocument($repoFeedUrl);
							$this->parseRepositoryFeedPage($repoFeed, $repoId, $knownPackages);
						}
						unset($repoFeed);

						// Update package index by exploring local metadata directories for the given repository
						$this->updatePackageIndex($repoId, $knownPackages);
						unset($knownPackages);
					}
				}
			}
		} catch (\Exception $e) {
			if (PHP_SAPI == 'cli') {
				fwrite(STDERR, $e->getMessage() . "\n");
				exit(1);
			}

			throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Release lock file
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->releaseLock();
	}

	/**
	 * Parse the given repository feed page and extract/download package metadata
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @param ApsDocument $repositoryFeed Document representing APS repository feed
	 * @param string $repositoryId Repository identifier (e.g. 1, 1.1, 1.2, 2.0 ...)
	 * @param ApsPackage[] &$knownPackages List of known packages in the repository
	 * @çeturn void
	 */
	protected function parseRepositoryFeedPage(ApsDocument $repositoryFeed, $repositoryId, &$knownPackages)
	{
		$entityManager = $this->getEntityManager();
		$metaFiles = array();
		$metadataDir = $this->getMetadataDir() . '/' . $repositoryId;

		// Parse all package entries
		foreach ($repositoryFeed->getXPathValue('root:entry', null, false) as $entry) {
			// Retrieves needed data
			$name = $repositoryFeed->getXPathValue('a:name/text()', $entry);
			$version = $repositoryFeed->getXPathValue('a:version/text()', $entry);
			$release = $repositoryFeed->getXPathValue('a:release/text()', $entry);
			$vendor = $repositoryFeed->getXPathValue('a:vendor/text()', $entry);
			$vendorURI = $repositoryFeed->getXPathValue('a:vendor_uri/text()', $entry) ?:
				$repositoryFeed->getXPathValue('a:homepage/text()', $entry);
			$url = $repositoryFeed->getXPathValue("root:link[@a:type='aps']/@href", $entry);
			$metaURL = $repositoryFeed->getXPathValue("root:link[@a:type='meta']/@href", $entry);
			$iconURL = $repositoryFeed->getXPathValue("root:link[@a:type='icon']/@href", $entry);
			$certLevel = $repositoryFeed->getXPathValue("root:link[@a:type='certificate']/a:level/text()", $entry) ?: 'none';

			// Continue only if all data are available
			if (
				$name != '' && $version != '' && $release != '' && $vendor != '' && $vendorURI != '' && $url != '' &&
				$metaURL != ''
			) {
				$packageMetadataDir = "$metadataDir/$name";
				$cVersion = null;
				$cRelease = null;
				$isKnown = false;
				if (isset($knownPackages[$name])) {
					$cVersion = $knownPackages[$name]->getVersion();
					$cRelease = $knownPackages[$name]->getRelease();
					$isKnown = true;
				}

				$needUpdate = ($isKnown) ? (version_compare("$cVersion.$cRelease", "$version.$release", '<')) : false;
				$isBroken = ($isKnown && !$needUpdate) ? (
					!file_exists("$packageMetadataDir/APP-META.xml") || filesize("$packageMetadataDir/APP-META.xml") == 0
				) : false;

				// Continue only if a newer version is available, or if there is no valid APP-META.xml
				if (!$isKnown || $needUpdate || $isBroken) {
					if ($needUpdate || $isBroken) {
						$package = $knownPackages[$name];

						if ($needUpdate) {
							$package->setStatus('outdated'); // Mark the package as outdated
						} else {
							$entityManager->remove($package); // Delete broken package (it will be re-indexed)
						}
					}

					// Create new package object
					$package = new ApsPackage();
					$package
						->setName($name)
						->setVersion($version)
						->setRelease($release)
						->setApsVersion($repositoryId)
						->setVendor($vendor)
						->setVendorUri($vendorURI)
						->setUrl($url)
						->setIconUrl($iconURL)
						->setCert($certLevel)
						->setStatus(in_array($name, $this->unlockedPackages) ? 'unlocked' : 'locked');
					$knownPackages[$name] = $package;

					// Create package metadata directory
					@mkdir($packageMetadataDir, 0750, true);

					// Schedule download of package APP-META.xml file
					$metaFiles[$name] = array('src' => $metaURL, 'trg' => "$packageMetadataDir/APP-META.xml");
				}
			}
		}

		if (!empty($metaFiles)) {
			$this->downloadFiles($metaFiles); // Download package APP-META.xml files
		}
	}

	/**
	 * Update package index
	 *
	 * @param string $repoId Repository unique identifier (e.g. 1, 1.1, 1.2, .2.0)
	 * @param ApsPackage[] $packages Packages
	 * @return void
	 */
	public function updatePackageIndex($repoId, $packages)
	{
		$metaDir = $this->getMetadataDir() . '/' . $repoId;
		$entityManager = $this->getEntityManager();

		// Retrieve list of all available packages
		$packageNames = array();
		foreach (new \DirectoryIterator($metaDir) as $fileInfo) {
			if (!$fileInfo->isDot() && $fileInfo->isDir()) {
				$packageNames[] = $fileInfo->getFileName();
			}
		}

		foreach ($packages as $package) {
			$name = $package->getName();

			if (!in_array($package->getName(), $packageNames)) { // Obsolete package
				$package->setStatus('obsolete');
			} elseif (!$entityManager->contains($package)) { // New package
				$metaFilePath = $metaDir . '/' . $name . '/APP-META.xml';

				if (file_exists($metaFilePath) && filesize($metaFilePath) != 0) { // Retrieves needed data
					$meta = new ApsDocument($metaDir . '/' . $name . '/APP-META.xml');

					if ($meta->getXPathValue('//aspnet:*', null, false)->length == 0) { // Ignore aspnet packages
						$summary = $meta->getXPathValue('//root:summary/text()');
						$category = $meta->getXPathValue('//root:category/text()');

						if ($summary != '' && $category != '') { // Only add valid packages
							$package
								->setSummary($summary)
								->setCategory($category);
							$entityManager->persist($package);
							continue;
						}
					}
				}

				utils_removeDir($metaDir . '/' . $name); // Remove ignored/invalid package metadata
			}
		}

		$entityManager->flush();

		foreach ($packages as $package) {
			$status = $package->getStatus();

			if (in_array($status, array('outdated', 'obsolete'))) {
				if (!$entityManager->getRepository('Aps:ApsInstance')->findOneBy(array('package' => $package))) {
					if ($status == 'obsolete') {
						utils_removeDir($metaDir . '/' . $package->getName());
					}

					utils_removeDir(
						$this->getPackageDir() . '/' . $package->getName() . '-' . $package->getVersion() . '-' .
						$package->getRelease() . '.app.zip'
					);

					$entityManager->remove($package);
				}
			}
		}

		$entityManager->flush();
	}

	/**
	 * Download the given files
	 *
	 * @param array $files Array describing list of files to download
	 * @return void
	 */
	protected function downloadFiles(array $files)
	{
		$config = Registry::get('config');
		$distroCAbundle = $config['DISTRO_CA_BUNDLE'];
		$distroCApath = $config['DISTRO_CA_PATH'];
		$files = array_chunk($files, 20); // Download 20 files at a time

		foreach ($files as $chunk) {
			$fileHandles = array();
			$curlHandles = array();
			$curlMultiHandle = curl_multi_init();

			// Create cURL handles (one per file) and add them to cURL multi handle
			for ($i = 0, $size = count($chunk); $i < $size; $i++) {
				$fileHandle = fopen($chunk[$i]['trg'], 'wb');
				$curlHandle = curl_init($chunk[$i]['src']);

				if (!$curlHandle || !$fileHandle) {
					throw new \RuntimeException('Could not create cURL or file handle');
				}

				curl_setopt_array($curlHandle, array(
					CURLOPT_BINARYTRANSFER => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FILE => $fileHandle,
					CURLOPT_TIMEOUT => 600,
					CURLOPT_FAILONERROR => true,
					CURLOPT_FOLLOWLOCATION => false, // Cannot be true when safe_mode or open_basedir are in effect
					CURLOPT_HEADER => false,
					CURLOPT_NOBODY => false,
					CURLOPT_SSL_VERIFYHOST => 2,
					CURLOPT_SSL_VERIFYPEER => true,
					CURLOPT_CAINFO => $distroCAbundle,
					CURLOPT_CAPATH => $distroCApath
				));

				$curlHandles[$i] = $curlHandle;
				$fileHandles[$i] = $fileHandle;
				curl_multi_add_handle($curlMultiHandle, $curlHandle);
			}

			do {
				curl_multi_exec($curlMultiHandle, $running); // Execute cURL handles
				curl_multi_select($curlMultiHandle); // Wait for activity

				// Follow location manually by updating the cUrl handle (URL).
				// This is a workaround for CURLOPT_FOLLOWLOCATION which cannot be true when safe_more or
				// open_basedir are in effect
				while ($info = curl_multi_info_read($curlMultiHandle)) {
					$handle = $info['handle']; // Get involved cURL handle
					$info = curl_getinfo($handle); // Get handle info

					if ($info['redirect_url']) {
						curl_multi_remove_handle($curlMultiHandle, $handle);
						curl_setopt($handle, CURLOPT_URL, $info['redirect_url']);
						curl_multi_add_handle($curlMultiHandle, $handle);
						$running = 1;
					}
				}
			} while ($running > 0);

			for ($i = 0, $size = count($chunk); $i < $size; $i++) { // Close cURL and file handles
				curl_multi_remove_handle($curlMultiHandle, $curlHandles[$i]);
				curl_close($curlHandles[$i]);
				fclose($fileHandles[$i]);
			}

			curl_multi_close($curlMultiHandle);
		}
	}

	/**
	 * Check for requirements
	 *
	 * @throw \RuntimeException if not all requirements are meets
	 * @return void
	 */
	protected function checkRequirements()
	{
		if (!ini_get('allow_url_fopen')) {
			throw new \RuntimeException('allow_url_fopen is disabled');
		}

		if (!function_exists('curl_version')) {
			throw new \RuntimeException('cURL extension is not available');
		}

		if (!function_exists('json_encode')) {
			throw new \RuntimeException('JSON support is not available');
		}

		if (!function_exists('posix_getuid')) {
			throw new \RuntimeException('Support for POSIX functions is not available');
		}

		if (PHP_SAPI == 'cli' && 0 != posix_getuid()) {
			throw new \RuntimeException('This script must be run as root user.');
		}
	}

	/**
	 * Setup environment
	 *
	 * @return void
	 */
	protected function setupEnvironment()
	{
		ignore_user_abort(1); // Do not abort on a client disconnection
		set_time_limit(0); // Tasks made by this service can take up several minutes to finish
		umask(027); // Set umask

		if (PHP_SAPI == 'cli') {
			// Set real user UID/GID of current process (panel user)
			$config = Registry::get('config');
			$panelUser = $config['SYSTEM_USER_PREFIX'] . $config['SYSTEM_USER_MIN_UID'];
			if (($info = @posix_getpwnam($panelUser)) === false) {
				throw new \RuntimeException(sprintf("Could not get info about the '%s' user.", $panelUser));
			}

			if (!@posix_initgroups($panelUser, $info['gid'])) {
				throw new \RuntimeException(sprintf(
					"could not calculates the group access list for the '%s' user", $panelUser
				));
			}

			// setgid must be called first, else it will fail
			if (!@posix_setgid($info['gid']) || !@posix_setuid($info['uid'])) {
				throw new \RuntimeException(sprintf('Could not change real user uid/gid of current process'));
			}
		}

		$this->acquireLock(); // Acquire exclusive lock to prevent multiple run
	}

	/**
	 * Acquire exclusive lock
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function acquireLock()
	{
		$this->lockFile = @fopen(GUI_ROOT_DIR . '/data/tmp/aps_spider_lock', 'w');
		if (!@flock($this->lockFile, LOCK_EX | LOCK_NB)) {
			throw new \Exception('Another instance is already running. Aborting...', 409);
		}
	}

	/**
	 * Release exclusive lock
	 *
	 * @return void
	 */
	public function releaseLock()
	{
		if ($this->lockFile) {
			@flock($this->lockFile, LOCK_UN);
			@fclose($this->lockFile);
		}
	}
}
