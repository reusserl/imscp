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
	 * Constructor
	 */
	public function __construct()
	{
		try {
			parent::__construct();

			// Ensure that all needed functions are available
			$this->checkRequirements();

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
				fwrite(STDERR, sprintf("Runtime error: %s\n", $e->getMessage()));
				exit(1);
			}

			throw $e;
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
			ignore_user_abort(1); // Do not abort on a client disconnection
			set_time_limit(0); // This task can take up several minutes to finish

			if (PHP_SAPI == 'cli') {
				if (0 == posix_getuid()) {
					throw new \RuntimeException('This script must be run as root user.');
				}

				// Set real user UID/GID of current process (panel user)
				$config = Registry::get('config');
				$panelUser = $config['SYSTEM_USER_PREFIX'] . $config['SYSTEM_USER_MIN_UID'];
				$info = posix_getpwnam($panelUser);
				posix_setgid($info['uid']);
				posix_setuid($info['gid']);
			}

			// Acquires exclusive lock to prevent multiple run
			$fpLock = @fopen(GUI_ROOT_DIR . '/data/tmp/aps_spider_lock', 'w');
			if (!@flock($fpLock, LOCK_EX | LOCK_NB)) {
				throw new \RuntimeException('Another instance is already running. Aborting...');
			}

			$baseURL = $this->getAPScatalogURL();
			$catalogDoc = new Document($baseURL, 'html');

			// Retrieves list of available APS standard repositories
			// See https://doc.apsstandard.org/2.1/portal/cat/browsing/#retrieving-repository-index
			$repos = $catalogDoc->getXPathValue("//a[@class='repository']/@href", null, false);

			foreach ($repos as $repo) {
				$repoPath = $repo->nodeValue;
				$repoId = rtrim($repoPath, '/');

				// Explore supported APS standard repositories only
				if (in_array($repoId, $this->apsVersions)) {
					// Discover repository feed path
					// See https://doc.apsstandard.org/2.1/portal/cat/browsing/#discovering-repository-feed
					$repoIndexDoc = new Document($baseURL . '/' . $repoPath, 'html');
					$repoFeedPath = $repoIndexDoc->getXPathValue("//a[@id='feedLink']/@href");
					unset($repoIndexDoc);

					if ($repoFeedPath != '') { // Ignore invalid repository entry
						// Explore the repository by chunk of 100 entries (we fetch only latest package versions)
						// See https://doc.apsstandard.org/2.1/portal/cat/search/#search-description-arguments
						$repoChunkDoc = new Document($baseURL . str_replace('../', '/', $repoFeedPath) . '?pageSize=100&latest=1');
						$this->exploreRepositoryChunk($repoChunkDoc, $repoId);
						while ($repoFeedPath = $repoChunkDoc->getXPathValue("root:link[@rel='next']/@href")) {
							$repoChunkDoc = new Document($repoFeedPath);
							$this->exploreRepositoryChunk($repoChunkDoc, $repoId);
						}
						unset($repoChunkDoc);

						// Update package index by exploring local metadata directory for the given repository
						$this->updatePackageIndex($repoId);
					}
				}
			}

			// Release exlusive lock
			@flock($fpLock, LOCK_UN);
			@fclose($fpLock);
			@unlink(GUI_ROOT_DIR . '/data/tmp/aps_spider_lock');
		} catch (\Exception $e) {
			if (PHP_SAPI == 'cli') {
				fwrite(STDERR, sprintf("Runtime error: %s\n", $e->getMessage()));
				exit(1);
			}

			throw $e;
		}
	}

	/**
	 * Process the given APS repository chunk
	 *
	 * @param Document $doc Document representing APS repository feed page
	 * @param string $repoId Repository unique identifier (e.g. 1, 1.1, 1.2, .2.0)
	 * @return void
	 */
	protected function exploreRepositoryChunk(Document $doc, $repoId)
	{
		$filesToDownload = array();
		$pkgMetaBasedir = $this->getPackageMetadataDir();

		foreach ($doc->getXPathValue("root:entry", null, false) as $entry) {
			// Retrieves needed data
			$pkgName = $doc->getXPathValue("a:name/text()", $entry);
			$pkgVersion = $doc->getXPathValue("a:version/text()", $entry);
			$pkgRelease = $doc->getXPathValue("a:release/text()", $entry);
			$vendor = $doc->getXPathValue("a:vendor/text()", $entry);
			$vendorURI = $doc->getXPathValue("a:vendor_uri/text()", $entry) ?: $doc->getXPathValue("a:homepage/text()", $entry);
			$pkgUrl = $doc->getXPathValue("root:link[@a:type='aps']/@href", $entry);
			$pkgMetaUrl = $doc->getXPathValue("root:link[@a:type='meta']/@href", $entry);
			$pkgIconUrl = $doc->getXPathValue("root:link[@a:type='icon']/@href", $entry);
			$pkgCertLevel = $doc->getXPathValue("root:link[@a:type='certificate']/a:level/text()", $entry) ?: 'none';

			// Continue only if all data are available
			if (
				$pkgName != '' && $pkgVersion != '' && $pkgRelease != '' && $vendor != '' && $vendorURI != '' &&
				$pkgUrl != '' && $pkgMetaUrl != ''
			) {
				// Package metadata directory
				$pkgMetaDir = "$pkgMetaBasedir/$repoId/$pkgName";

				$pkgCversion = null;
				$pkgCrelease = null;
				if (isset($this->packages[$repoId][$pkgName])) {
					$pkgCversion = $this->packages[$repoId][$pkgName]['version'];
					$pkgCrelease = $this->packages[$repoId][$pkgName]['release'];
				}

				$isKnowVersion = !is_null($pkgCversion);
				$isOutDatedVersion = ($isKnowVersion)
					? (version_compare($pkgCversion, $pkgVersion, '<') || version_compare($pkgCrelease, $pkgRelease, '<'))
					: false;

				// Continue only if a newer version is available, or if there is no valid APP-META.xml or APP-DATA.json file
				if (
					(!$isKnowVersion || $isOutDatedVersion) ||
					!file_exists("$pkgMetaDir/APP-META.xml") || filesize("$pkgMetaDir/APP-META.xml") == 0 ||
					!file_exists("$pkgMetaDir/APP-DATA.json") || filesize("$pkgMetaDir/APP-DATA.json") == 0
				) {
					// Delete out-dated version if any
					if ($isOutDatedVersion) {
						utils_removeDir("$pkgMetaBasedir/$repoId/$pkgName");
						exec_query(
							'
								DELETE FROM `aps_packages`
								WHERE `name` = ? AND aps_version = ? AND `version` = ? AND `release` = ?
							',
							array($pkgName, $repoId, $pkgCversion, $pkgCrelease)
						);
						unset($filesToDownload[$pkgName]);
					}

					// Marks this package as seen
					$this->packages[$repoId][$pkgName] = array('version' => $pkgVersion, 'release' => $pkgRelease);

					// Create package metadata directory
					@mkdir($pkgMetaDir, 0750, true);

					// Save intermediate metadata
					@file_put_contents("$pkgMetaDir/APP-DATA.json", json_encode(array(
						'app_url' => $pkgUrl, 'app_icon_url' => $pkgIconUrl, 'app_cert_level' => $pkgCertLevel,
						'app_vendor' => $vendor, 'app_vendor_uri' => $vendorURI
					)));

					// Schedule download of APP-META.xml file
					$filesToDownload[$pkgName] = array('src' => $pkgMetaUrl, 'trg' => "$pkgMetaDir/APP-META.xml");
				}
			}
		}

		if (!empty($filesToDownload)) {
			$this->downloadFiles($filesToDownload); // Download package APP-META.xml files
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
		$packages = array();
		$metadataDirectory = $this->getPackageMetadataDir() . '/' . $repoId;
		$directory = @dir($metadataDirectory);

		if (!$directory) {
			throw new \RuntimeException(sprintf('Could not read directory: %s', $php_errormsg));
		}

		// Retrieve list of packages
		while ($pkgDir = $directory->read()) {
			if ($pkgDir != '.' && $pkgDir != '..') {
				$packages[] = $pkgDir;
			}
		}

		// Find package for which metadata are no longer available and removes them from database
		if (isset($this->packages[$repoId])) {
			$pkgToDelete = array_diff(array_keys($this->packages[$repoId]), $packages);
			foreach ($pkgToDelete as $pkgName) {
				exec_query('DELETE FROM `aps_packages` WHERE `name` = ? AND aps_version = ?', array($pkgName, $repoId));
			}
			unset($pkgToDelete);
		}

		// Add new package in database
		if (!empty($packages)) {
			foreach ($packages as $package) {
				$metaFilePath = $metadataDirectory . '/' . $package . '/APP-META.xml';
				$dataFilePath = $metadataDirectory . '/' . $package . '/APP-DATA.json';

				// Retrieves needed data
				if (
					file_exists($metaFilePath) && filesize($metaFilePath) != 0 &&
					file_exists($dataFilePath) && filesize($dataFilePath) != 0
				) {
					$metaDoc = new Document($metadataDirectory . '/' . $package . '/APP-META.xml');
					$name = $metaDoc->getXPathValue('root:name/text()');
					$summary = $metaDoc->getXPathValue('//root:summary/text()');
					$version = $metaDoc->getXPathValue('root:version/text()');
					$release = $metaDoc->getXPathValue('root:release/text()');
					$apsVersion = $repoId;
					$category = $metaDoc->getXPathValue('//root:category/text()');

					// Get intermediate data
					$data = json_decode(file_get_contents($dataFilePath), JSON_OBJECT_AS_ARRAY);
					$vendor = isset($data['app_vendor']) ? $data['app_vendor'] : '';
					$vendorURI = isset($data['app_vendor_uri']) ? $data['app_vendor_uri'] : '';
					$url = isset($data['app_url']) ? $data['app_url'] : '';
					$iconUrl = isset($data['app_icon_url']) ? $data['app_icon_url'] : '';
					$certLevel = isset($data['app_cert_level']) ? $data['app_cert_level'] : '';

					// Only add valid packages
					if (
						$name != '' && $summary != '' && $version != '' && $release != '' && $category != '' &&
						$vendor != '' && $vendorURI != '' && $url && $iconUrl && $certLevel
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
								$name, $summary, $version, $apsVersion, $release, $category, $vendor, $vendorURI, $url,
								$iconUrl, $certLevel, 'disabled'
							)
						);
					} else {
						utils_removeDir($metadataDirectory . '/' . $package); // Remove invalid package
					}
				} else {
					utils_removeDir($metadataDirectory . '/' . $package); // Remove invalid package
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
		$files = array_chunk($files, 20); // Download by chunk of 20 files at once

		foreach ($files as $chunk) {
			$fileHandles = array();
			$curlHandles = array();
			$curlMultiHandle = curl_multi_init();

			// Create cURL handles (one per file) and add them to cURL multi handle
			for ($i = 0, $size = count($chunk); $i < $size; $i++) {
				$fileHandle = @fopen($chunk[$i]['trg'], 'wb');
				$curlHandle = @curl_init($chunk[$i]['src']);

				if (!$curlHandle || !$fileHandle) {
					continue;
				}

				curl_setopt_array($curlHandle, array(
					CURLOPT_BINARYTRANSFER => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FILE => $fileHandle,
					CURLOPT_TIMEOUT => 240,
					CURLOPT_FAILONERROR => true,
					CURLOPT_FOLLOWLOCATION => false, // Cannot be true when safe_mode or openbase_dir are in effect
					CURLOPT_HEADER => false,
					CURLOPT_NOBODY => false,
					CURLOPT_SSL_VERIFYHOST => 2,
					CURLOPT_SSL_VERIFYPEER => false
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
				// openbase_dir are in effect
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
			throw new \RuntimeException('allow_url_fopen is disabled');
		}

		if (!function_exists('curl_version')) {
			throw new \RuntimeException('cURL extension is not available');
		}

		if (!function_exists('json_encode')) {
			throw new \RuntimeException('JSON support is not available');
		}

		if (!function_exists('posix_getuid')) {
			throw new \RuntimeException('Support for POSIX function is not available');
		}
	}
}
