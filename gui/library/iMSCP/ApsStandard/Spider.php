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

namespace iMSCP\ApsStandard;

use iMSCP_Registry as Registry;

/**
 * Class Spider
 *
 * @package iMSCP\ApsStandard
 */
class Spider extends ApsStandardAbstract
{
	/**
	 * @var array Known packages
	 */
	protected $packages = array();

	/**
	 * @var resource Lock file
	 */
	protected $lockFile;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		try {
			parent::__construct();

			// Ensure that all needed functions are available
			$this->checkRequirements();

			// Setup environment
			$this->setupEnvironment();

			// Acquires exclusive lock to prevent multiple run
			$this->lockFile = @fopen(GUI_ROOT_DIR . '/data/tmp/aps_spider_lock', 'w');
			if (!@flock($this->lockFile, LOCK_EX | LOCK_NB)) {
				throw new \RuntimeException(tr('Another instance is already running. Aborting...'));
			}

			// Retrieves list of known packages
			$stmt = exec_query('SELECT `name`, `version`, `aps_version`, `release` FROM `aps_packages`');
			if ($stmt->rowCount()) {
				while ($row = $stmt->fetchRow(\PDO::FETCH_ASSOC)) {
					$this->packages[$row['aps_version']][$row['name']] = array(
						'version' => $row['version'],
						'release' => $row['release']
					);
				}
			}
		} catch (\Exception $e) {
			if (PHP_SAPI == 'cli') {
				fwrite(STDERR, sprintf(tr("Runtime error: %s\n"), $e->getMessage()));
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
		// Release exlusive lock if any
		if ($this->lockFile) {
			@flock($this->lockFile, LOCK_UN);
			@fclose($this->lockFile);
			@unlink(GUI_ROOT_DIR . '/data/tmp/aps_spider_lock');
		}
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
			$systemIndex = new Document($serviceUrl, 'html');

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
					$repositoryIndex = new Document($serviceUrl . '/' . $repositoryUrl, 'html');
					$repositoryFeedUrl = $repositoryIndex->getXPathValue("//a[@id='feedLink']/@href");
					unset($repositoryIndex);

					if ($repositoryFeedUrl != '') { // Ignore invalid repository entry
						// Parse the repository feed by chunk of 100 entries (we fetch only latest package versions)
						// See: https://doc.apsstandard.org/2.1/portal/cat/search/#search-description-arguments
						$repositoryFeed = new Document(
							$serviceUrl . str_replace('../', '/', $repositoryFeedUrl) . '?pageSize=100&latest=1'
						);
						$this->parseRepositoryFeedPage($repositoryFeed, $repositoryId);
						while ($repositoryFeedUrl = $repositoryFeed->getXPathValue("root:link[@rel='next']/@href")) {
							$repositoryFeed = new Document($repositoryFeedUrl);
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
				fwrite(STDERR, sprintf(tr("Runtime error: %s\n"), $e->getMessage()));
				exit(1);
			}

			throw $e;
		}
	}

	/**
	 * Parse the given repository feed page and extract/download package metadata
	 *
	 * @param Document $repositoryFeed Document representing APS repository feed
	 * @param string $repositoryId Repository unique identifier (e.g. 1, 1.1, 1.2, 2.0 ...)
	 * @return void
	 */
	protected function parseRepositoryFeedPage(Document $repositoryFeed, $repositoryId)
	{
		$metaFiles = array();
		$metadataDir = $this->getPackageMetadataDir() . '/' . $repositoryId;
		$knownPkgs = isset($this->packages[$repositoryId]) ? $this->packages[$repositoryId] : array();

		// Parse all package entries
		foreach ($repositoryFeed->getXPathValue("root:entry", null, false) as $entry) {
			// Retrieves needed data
			$pkgName = $repositoryFeed->getXPathValue("a:name/text()", $entry);
			$pkgVersion = $repositoryFeed->getXPathValue("a:version/text()", $entry);
			$pkgRelease = $repositoryFeed->getXPathValue("a:release/text()", $entry);
			$vendor = $repositoryFeed->getXPathValue("a:vendor/text()", $entry);
			$vendorUri = $repositoryFeed->getXPathValue("a:vendor_uri/text()", $entry) ?:
				$repositoryFeed->getXPathValue("a:homepage/text()", $entry);
			$pkgUrl = $repositoryFeed->getXPathValue("root:link[@a:type='aps']/@href", $entry);
			$pkgMetaUrl = $repositoryFeed->getXPathValue("root:link[@a:type='meta']/@href", $entry);
			$pkgIconUrl = $repositoryFeed->getXPathValue("root:link[@a:type='icon']/@href", $entry);
			$pkgCertLevel = $repositoryFeed->getXPathValue("root:link[@a:type='certificate']/a:level/text()", $entry) ?: 'none';

			// Continue only if all data are available
			if (
				$pkgName != '' && $pkgVersion != '' && $pkgRelease != '' && $vendor != '' && $vendorUri != '' &&
				$pkgUrl != '' && $pkgMetaUrl != ''
			) {
				// Package metadata directory
				$pkgMetadataDir = "$metadataDir/$pkgName";

				$pkgCversion = null;
				$pkgCrelease = null;
				if (isset($knownPkgs[$pkgName])) {
					$pkgCversion = $knownPkgs[$pkgName]['version'];
					$pkgCrelease = $knownPkgs[$pkgName]['release'];
				}

				$isKnowVersion = !is_null($pkgCversion);
				$isOutDatedVersion = ($isKnowVersion)
					? (version_compare($pkgCversion, $pkgVersion, '<') || version_compare($pkgCrelease, $pkgRelease, '<'))
					: false;

				// Continue only if a newer version is available, or if there is no valid APP-META.xml or APP-DATA.json file
				if (
					(!$isKnowVersion || $isOutDatedVersion) ||
					!file_exists("$pkgMetadataDir/APP-META.xml") || filesize("$pkgMetadataDir/APP-META.xml") == 0 ||
					!file_exists("$pkgMetadataDir/APP-META.json") || filesize("$pkgMetadataDir/APP-META.json") == 0
				) {
					// Delete out-dated version if any
					if ($isOutDatedVersion) {
						utils_removeDir("$metadataDir/$pkgName");
						exec_query(
							'
								DELETE FROM `aps_packages`
								WHERE `name` = ? AND aps_version = ? AND `version` = ? AND `release` = ?
							',
							array($pkgName, $repositoryId, $pkgCversion, $pkgCrelease)
						);
						unset($metaFiles[$pkgName]);
					}

					// Marks this package as seen
					$knownPkgs[$pkgName] = array('version' => $pkgVersion, 'release' => $pkgRelease);

					// Create package metadata directory
					@mkdir($pkgMetadataDir, 0750, true);

					// Save intermediate metadata
					@file_put_contents("$pkgMetadataDir/APP-META.json", json_encode(array(
						'app_url' => $pkgUrl, 'app_icon_url' => $pkgIconUrl, 'app_cert_level' => $pkgCertLevel,
						'app_vendor' => $vendor, 'app_vendor_uri' => $vendorUri
					)));

					// Schedule download of APP-META.xml file
					$metaFiles[$pkgName] = array('src' => $pkgMetaUrl, 'trg' => "$pkgMetadataDir/APP-META.xml");
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
		$newPkgs = array();
		$knownPkgs = isset($this->packages[$repoId]) ? array_keys($this->packages[$repoId]) : array();
		$metadataDir = $this->getPackageMetadataDir() . '/' . $repoId;

		// Retrieve list of packages
		$directoryIterator = new \DirectoryIterator($metadataDir);
		foreach ($directoryIterator as $fileInfo) {
			if (!$fileInfo->isDot() && $fileInfo->isDir()) {
				$newPkgs[] = $fileInfo->getFileName();
			}
		}

		// Find packages for which metadata are no longer available and removes them from database
		if (isset($this->packages[$repoId])) {
			$pkgsToDelete = array_diff($knownPkgs, $newPkgs);
			foreach ($pkgsToDelete as $pkgName) {
				exec_query('DELETE FROM `aps_packages` WHERE `name` = ? AND aps_version = ?', array($pkgName, $repoId));
			}
			unset($pkgsToDelete);
		}

		// Add new packages in database
		if (!empty($newPkgs)) {
			$newPkgs = array_diff($newPkgs, $knownPkgs);
			foreach ($newPkgs as $pkg) {
				$metaFilePath = $metadataDir . '/' . $pkg . '/APP-META.xml';
				$dataFilePath = $metadataDir . '/' . $pkg . '/APP-META.json';

				// Retrieves needed data
				if (
					file_exists($metaFilePath) && filesize($metaFilePath) != 0 &&
					file_exists($dataFilePath) && filesize($dataFilePath) != 0
				) {
					$metaDoc = new Document($metadataDir . '/' . $pkg . '/APP-META.xml');
					$pkgName = $metaDoc->getXPathValue('root:name/text()');
					$pkgSummary = $metaDoc->getXPathValue('//root:summary/text()');
					$pkgVersion = $metaDoc->getXPathValue('root:version/text()');
					$pkgRelease = $metaDoc->getXPathValue('root:release/text()');
					$pkgCategory = $metaDoc->getXPathValue('//root:category/text()');

					// Get intermediate metadata
					$data = json_decode(file_get_contents($dataFilePath), JSON_OBJECT_AS_ARRAY);
					$pkgVendor = isset($data['app_vendor']) ? $data['app_vendor'] : '';
					$pkgVendorURI = isset($data['app_vendor_uri']) ? $data['app_vendor_uri'] : '';
					$pkgUrl = isset($data['app_url']) ? $data['app_url'] : '';
					$pkgIconUrl = isset($data['app_icon_url']) ? $data['app_icon_url'] : '';
					$pkgCertLevel = isset($data['app_cert_level']) ? $data['app_cert_level'] : '';

					// Only add valid packages
					if (
						$pkgName != '' && $pkgSummary != '' && $pkgVersion != '' && $pkgRelease != '' &&
						$pkgCategory != '' && $pkgVendor != '' && $pkgVendorURI != '' && $pkgUrl && $pkgIconUrl &&
						$pkgCertLevel
					) {
						exec_query(
							'
								INSERT INTO aps_packages (
									`name`, `summary`, `version`, `aps_version`, `release`, `category`, `vendor`,
									`vendor_uri`, `url`, `icon_url`, `cert`, `status`
								) VALUES(
									?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
								)
							',
							array(
								$pkgName, $pkgSummary, $pkgVersion, $repoId, $pkgRelease, $pkgCategory, $pkgVendor,
								$pkgVendorURI, $pkgUrl, $pkgIconUrl, $pkgCertLevel, 'disabled'
							)
						);
					} else {
						utils_removeDir($metadataDir . '/' . $pkg); // Remove invalid package
					}
				} else {
					utils_removeDir($metadataDir . '/' . $pkg); // Remove invalid package
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
		# Get needed configuration parameters
		$config = Registry::get('config');
		$distroCAbundle = $config['DISTRO_CA_BUNDLE'];
		$distroCApath = $config['DISTRO_CA_PATH'];

		// We download by chunk of 20 files at once
		$files = array_chunk($files, 20);

		foreach ($files as $chunk) {
			$fileHandles = array();
			$curlHandles = array();
			$curlMultiHandle = curl_multi_init();

			// Create cURL handles (one per file) and add them to cURL multi handle
			for ($i = 0, $size = count($chunk); $i < $size; $i++) {
				$fileHandle = fopen($chunk[$i]['trg'], 'wb');
				$curlHandle = curl_init($chunk[$i]['src']);

				if (!$curlHandle || !$fileHandle) {
					throw new \RuntimeException(tr("Runtime error: %s\n", tr('Could not create cURL or file handle')));
				}

				curl_setopt_array($curlHandle, array(
					CURLOPT_BINARYTRANSFER => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FILE => $fileHandle,
					CURLOPT_TIMEOUT => 300,
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

			// Close cURL and file handles
			for ($i = 0, $size = count($chunk); $i < $size; $i++) {
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
			throw new \RuntimeException(tr("Runtime error: %s\n", tr('allow_url_fopen is disabled')));
		}

		if (!function_exists('curl_version')) {
			throw new \RuntimeException(tr("Runtime error: %s\n", tr('cURL extension is not available')));
		}

		if (!function_exists('json_encode')) {
			throw new \RuntimeException(tr("Runtime error: %s\n", tr('JSON support is not available')));
		}

		if (!function_exists('posix_getuid')) {
			throw new \RuntimeException(tr("Runtime error: %s\n", tr('Support for POSIX functions is not available')));
		}

		if (0 != posix_getuid()) {
			throw new \RuntimeException(tr("Runtime error: %s\n", tr('This script must be run as root user.')));
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
		set_time_limit(0); // Tasks made by this object can take up several minutes to finish

		// Set umask
		umask(027);

		if (PHP_SAPI == 'cli') {
			// Set real user UID/GID of current process (panel user)
			$config = Registry::get('config');
			$panelUser = $config['SYSTEM_USER_PREFIX'] . $config['SYSTEM_USER_MIN_UID'];
			if (($info = @posix_getpwnam($panelUser)) === false) {
				throw new \RuntimeException(tr(
					"Runtime error: %s\n", tr("Could not get info about the '%s' user.", $panelUser)
				));
			}

			if (!@posix_initgroups($panelUser, $info['gid'])) {
				throw new \RuntimeException(tr(
					"Runtime error: %s\n", tr("could not calculates the group access list for the '%s' user", $panelUser)
				));
			}

			// setgid must be called first, else it will fail
			if (!@posix_setgid($info['gid']) || !@posix_setuid($info['uid'])) {
				throw new \RuntimeException(tr(
					"Runtime error: %s \n", tr('Could not change real user uid/gid of current process')
				));
			}
		}
	}
}
