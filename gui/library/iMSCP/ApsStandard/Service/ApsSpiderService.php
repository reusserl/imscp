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
use iMSCP_Registry as Registry;

/**
 * Class ApsSpiderService
 * @package iMSCP\ApsStandard\Service
 */
class ApsSpiderService extends AbstractApsService
{
	/**
	 * @var array Known packages
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
	 * Constructor
	 *
	 * @param EntityManager $entityManager
	 * @throws \Exception
	 */
	public function __construct(EntityManager $entityManager)
	{
		try {
			parent::__construct($entityManager);
			$this->checkRequirements();
			$this->setupEnvironment();

			// Retrieves list of known packages
			$stmt = $this->getEntityManager()->getConnection()->executeQuery(
				'SELECT `name`, `version`, `aps_version`, `release`, `status` FROM `aps_packages`'
			);
			if ($stmt->rowCount()) {
				while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					$this->packages[$row['aps_version']][$row['name']] = array(
						'version' => $row['version'],
						'release' => $row['release']
					);

					if ($row['status'] === 'ok') {
						$this->unlockedPackages[] = $row['name'];
					}
				}
			}
		} catch (\Exception $e) {
			if (PHP_SAPI == 'cli') {
				fwrite(STDERR, sprintf($e->getMessage()));
				exit(1);
			}

			throw $e;
		}
	}

	/**
	 * Remove lock file
	 * @return void
	 */
	public function __destruct()
	{
		$this->releaseLock();
	}

	/**
	 * Explore APS standard catalog
	 *
	 * Return void
	 */
	public function exploreCatalog()
	{
		try {
			$serviceUrl = $this->getServiceURL();
			$systemIndex = new ApsDocument($serviceUrl, 'html');

			// Parse system index to retrieve list of available repositories
			// See: https://doc.apsstandard.org/2.1/portal/cat/browsing/#retrieving-repository-index
			$repositories = $systemIndex->getXPathValue("//a[@class='repository']/@href", null, false);

			foreach ($repositories as $repository) {
				$repositoryUrl = $repository->nodeValue;
				$repositoryId = rtrim($repositoryUrl, '/');

				// Explores supported APS standard repositories only
				if (in_array($repositoryId, $this->supportedRepositories)) {
					// Discover repository feed
					// See: https://doc.apsstandard.org/2.1/portal/cat/browsing/#discovering-repository-feed
					$repositoryIndex = new ApsDocument($serviceUrl . '/' . $repositoryUrl, 'html');
					$repositoryFeedUrl = $repositoryIndex->getXPathValue("//a[@id='feedLink']/@href");
					unset($repositoryIndex);

					if ($repositoryFeedUrl != '') { // Ignore invalid repository entry
						// Parse the repository feed by chunk of 100 entries (we fetch only latest package versions)
						// See: https://doc.apsstandard.org/2.1/portal/cat/search/#search-description-arguments
						$repositoryFeed = new ApsDocument(
							$serviceUrl . str_replace('../', '/', $repositoryFeedUrl) . '?pageSize=100&latest=1'
						);
						$this->parseRepositoryFeedPage($repositoryFeed, $repositoryId);
						while ($repositoryFeedUrl = $repositoryFeed->getXPathValue("root:link[@rel='next']/@href")) {
							$repositoryFeed = new ApsDocument($repositoryFeedUrl);
							$this->parseRepositoryFeedPage($repositoryFeed, $repositoryId);
						}
						unset($repositoryFeed);

						// Update package index by exploring local metadata directories for the given repository
						$this->updatePackageIndex($repositoryId);
					}
				}
			}
		} catch (\Exception $e) {
			if (PHP_SAPI == 'cli') {
				fwrite(STDERR, sprintf($e->getMessage()));
				exit(1);
			}

			throw $e;
		}
	}

	/**
	 * Parse the given repository feed page and extract/download package metadata
	 *
	 * @param ApsDocument $repositoryFeed Document representing APS repository feed
	 * @param string $repositoryId Repository unique identifier (e.g. 1, 1.1, 1.2, 2.0 ...)
	 * @return void
	 */
	protected function parseRepositoryFeedPage(ApsDocument $repositoryFeed, $repositoryId)
	{
		$metaFiles = array();
		$metadataDir = $this->getMetadataDir() . '/' . $repositoryId;
		$knownPackages = isset($this->packages[$repositoryId]) ? $this->packages[$repositoryId] : array();

		// Parse all package entries
		foreach ($repositoryFeed->getXPathValue("root:entry", null, false) as $entry) {
			// Retrieves needed data
			$packageName = $repositoryFeed->getXPathValue("a:name/text()", $entry);
			$packageVersion = $repositoryFeed->getXPathValue("a:version/text()", $entry);
			$packageRelease = $repositoryFeed->getXPathValue("a:release/text()", $entry);
			$packageVendor = $repositoryFeed->getXPathValue("a:vendor/text()", $entry);
			$packageVendorUri = $repositoryFeed->getXPathValue("a:vendor_uri/text()", $entry) ?:
				$repositoryFeed->getXPathValue("a:homepage/text()", $entry);
			$packageUrl = $repositoryFeed->getXPathValue("root:link[@a:type='aps']/@href", $entry);
			$packageMetaUrl = $repositoryFeed->getXPathValue("root:link[@a:type='meta']/@href", $entry);
			$packageIconUrl = $repositoryFeed->getXPathValue("root:link[@a:type='icon']/@href", $entry);
			$packageCertLevel = $repositoryFeed->getXPathValue(
				"root:link[@a:type='certificate']/a:level/text()", $entry
			) ?: 'none';

			// Continue only if all data are available
			if (
				$packageName != '' && $packageVersion != '' && $packageRelease != '' && $packageVendor != '' &&
				$packageVendorUri != '' && $packageUrl != '' && $packageMetaUrl != ''
			) {
				$packageMetadataDir = "$metadataDir/$packageName";
				$packageCurrentVersion = null;
				$packageCurrentRelease = null;
				if (isset($knownPackages[$packageName])) {
					$packageCurrentVersion = $knownPackages[$packageName]['version'];
					$packageCurrentRelease = $knownPackages[$packageName]['release'];
				}

				$isKnowVersion = !is_null($packageCurrentVersion);
				$isOutDatedVersion = ($isKnowVersion) ? (
					version_compare($packageCurrentVersion, $packageVersion, '<') ||
					version_compare($packageCurrentRelease, $packageRelease, '<')
				) : false;

				// Continue only if a newer version is available, or if there is no valid APP-META.xml or APP-DATA.json file
				if (
					(!$isKnowVersion || $isOutDatedVersion) ||
					!file_exists("$packageMetadataDir/APP-META.xml") || filesize("$packageMetadataDir/APP-META.xml") == 0 ||
					!file_exists("$packageMetadataDir/APP-META.json") || filesize("$packageMetadataDir/APP-META.json") == 0
				) {
					if ($isOutDatedVersion) { // Delete out-dated version if any
						utils_removeDir("$metadataDir/$packageName");
						$stmt = $this->entityManager->getConnection()->prepare(
							'
								DELETE FROM `aps_packages`
								WHERE `name` = ? AND aps_version = ? AND `version` = ? AND `release` = ?
							'
						);
						$stmt->execute(array($packageName, $repositoryId, $packageCurrentVersion, $packageCurrentRelease));
						unset($metaFiles[$packageName]);
					}

					// Marks this package as seen
					$knownPackages[$packageName] = array('version' => $packageVersion, 'release' => $packageRelease);
					// Create package metadata directory
					@mkdir($packageMetadataDir, 0750, true);
					// Save intermediate metadata
					@file_put_contents("$packageMetadataDir/APP-META.json", json_encode(array(
						'app_url' => $packageUrl, 'app_icon_url' => $packageIconUrl, 'app_cert_level' => $packageCertLevel,
						'app_vendor' => $packageVendor, 'app_vendor_uri' => $packageVendorUri
					)));

					// Schedule download of APP-META.xml file
					$metaFiles[$packageName] = array('src' => $packageMetaUrl, 'trg' => "$packageMetadataDir/APP-META.xml");
				}
			}
		}

		if (!empty($metaFiles)) {
			$this->downloadFiles($metaFiles); // Download package APP-META.xml files
		}
	}

	/**
	 * Update package index by exploring package metadata directories for the given repository
	 *
	 * @param string $repoId Repository unique identifier (e.g. 1, 1.1, 1.2, .2.0)
	 * @return void
	 */
	public function updatePackageIndex($repoId)
	{
		$newPackages = array();
		$knownPackages = isset($this->packages[$repoId]) ? array_keys($this->packages[$repoId]) : array();
		$metadataDir = $this->getMetadataDir() . '/' . $repoId;

		// Retrieve list of packages
		$directoryIterator = new \DirectoryIterator($metadataDir);
		foreach ($directoryIterator as $fileInfo) {
			if (!$fileInfo->isDot() && $fileInfo->isDir()) {
				$newPackages[] = $fileInfo->getFileName();
			}
		}

		if (isset($this->packages[$repoId])) { // Find obsolete packages and removes them from database
			$obsoletePackages = array_diff($knownPackages, $newPackages);
			if (!empty($obsoletePackages)) {
				$stmt = $this->getEntityManager()->getConnection()->prepare(
					'DELETE FROM `aps_packages` WHERE `name` = ? AND `aps_version` = ?'
				);

				foreach ($obsoletePackages as $packageName) {
					$stmt->execute(array($packageName, $repoId));
				}
			}
			unset($obsoletePackages);
		}

		// Add new packages in database
		$newPackages = array_diff($newPackages, $knownPackages);
		if (!empty($newPackages)) {
			$stmt = $this->getEntityManager()->getConnection()->prepare(
				'
					INSERT INTO aps_packages (
						`name`, `summary`, `version`, `aps_version`, `release`, `category`, `vendor`, `vendor_uri`,
						`url`, `icon_url`, `cert`, `status`
					) VALUES(
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
					)
				'
			);

			foreach ($newPackages as $package) {
				$packageMetaFilePath = $metadataDir . '/' . $package . '/APP-META.xml';
				$packageIntermediateMetaFilePath = $metadataDir . '/' . $package . '/APP-META.json';

				if ( // Retrieves needed data
					file_exists($packageMetaFilePath) && filesize($packageMetaFilePath) != 0 &&
					file_exists($packageIntermediateMetaFilePath) && filesize($packageIntermediateMetaFilePath) != 0
				) {
					$metadata = new ApsDocument($metadataDir . '/' . $package . '/APP-META.xml');
					$packageName = $metadata->getXPathValue('root:name/text()');
					$packageSummary = $metadata->getXPathValue('//root:summary/text()');
					$packageVersion = $metadata->getXPathValue('root:version/text()');
					$packageRelease = $metadata->getXPathValue('root:release/text()');
					$packageCategory = $metadata->getXPathValue('//root:category/text()');

					// Get intermediate metadata
					$metadata = json_decode(file_get_contents($packageIntermediateMetaFilePath), JSON_OBJECT_AS_ARRAY);
					$packageVendor = isset($metadata['app_vendor']) ? $metadata['app_vendor'] : '';
					$packageVendorURI = isset($metadata['app_vendor_uri']) ? $metadata['app_vendor_uri'] : '';
					$packageUrl = isset($metadata['app_url']) ? $metadata['app_url'] : '';
					$packageIconUrl = isset($metadata['app_icon_url']) ? $metadata['app_icon_url'] : '';
					$packageCertLevel = isset($metadata['app_cert_level']) ? $metadata['app_cert_level'] : '';

					if ( // Only add valid packages
						$packageName != '' && $packageSummary != '' && $packageVersion != '' && $packageRelease != '' &&
						$packageCategory != '' && $packageVendor != '' && $packageVendorURI != '' && $packageUrl &&
						$packageIconUrl && $packageCertLevel
					) {
						$stmt->execute(array(
							$packageName, $packageSummary, $packageVersion, $repoId, $packageRelease, $packageCategory,
							$packageVendor, $packageVendorURI, $packageUrl, $packageIconUrl, $packageCertLevel,
							(isset($this->unlockedPackages[$packageName])) ? 'ok' : 'disabled'
						));
					} else {
						utils_removeDir($metadataDir . '/' . $package); // Remove invalid package
					}
				} else {
					utils_removeDir($metadataDir . '/' . $package); // Remove invalid package
				}
			}
		}
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
		$files = array_chunk($files, 20); // Download by chunk of 20 files at once

		foreach ($files as $chunk) {
			$fileHandles = array();
			$curlHandles = array();
			$curlMultiHandle = curl_multi_init();

			// Create cURL handles (one per file) and add them to cURL multi handle
			for ($i = 0, $size = count($chunk); $i < $size; $i++) {
				$fileHandle = fopen($chunk[$i]['trg'], 'wb');
				$curlHandle = curl_init($chunk[$i]['src']);

				if (!$curlHandle || !$fileHandle) {
					throw new \RuntimeException(tr('Could not create cURL or file handle'));
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
			throw new \RuntimeException(tr('allow_url_fopen is disabled'));
		}

		if (!function_exists('curl_version')) {
			throw new \RuntimeException(tr('cURL extension is not available'));
		}

		if (!function_exists('json_encode')) {
			throw new \RuntimeException(tr('JSON support is not available'));
		}

		if (!function_exists('posix_getuid')) {
			throw new \RuntimeException(tr('Support for POSIX functions is not available'));
		}

		if (PHP_SAPI == 'cli' && 0 != posix_getuid()) {
			throw new \RuntimeException(tr('This script must be run as root user.'));
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
				throw new \RuntimeException(tr(
					'Runtime error: %s', tr("Could not get info about the '%s' user.", $panelUser)
				));
			}

			if (!@posix_initgroups($panelUser, $info['gid'])) {
				throw new \RuntimeException(tr(
					'Runtime error: %s', tr("could not calculates the group access list for the '%s' user", $panelUser)
				));
			}

			// setgid must be called first, else it will fail
			if (!@posix_setgid($info['gid']) || !@posix_setuid($info['uid'])) {
				throw new \RuntimeException(tr(
					'Runtime error: %s', tr('Could not change real user uid/gid of current process')
				));
			}
		}

		$this->acquireLock(); // Acquire exclusive lock to prevent multiple run
	}

	/**
	 * Acquire exclusive lock
	 *
	 * @return void
	 */
	public function acquireLock()
	{
		$this->lockFile = @fopen(GUI_ROOT_DIR . '/data/tmp/aps_spider_lock', 'w');
		if (!@flock($this->lockFile, LOCK_EX | LOCK_NB)) {
			throw new \RuntimeException(tr('Another instance is already running. Aborting...'));
		}
	}

	/**
	 * Release exclusive lock
	 */
	public function releaseLock()
	{
		if ($this->lockFile) {
			@flock($this->lockFile, LOCK_UN);
			@fclose($this->lockFile);
		}
	}
}
