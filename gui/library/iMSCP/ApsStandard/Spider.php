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

			if (!ini_get('allow_url_fopen')) {
				throw new \RuntimeException('allow_url_fopen is disabled');
			}

			if (!function_exists('curl_version')) {
				throw new \RuntimeException('cURL extension is not available');
			}

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

						// Update package index by exploring local metadatas directory for the given repository
						$this->updatePackageIndex($repoId);
					}
				}
			}

			// Release exlusive lock
			@flock($fpLock, LOCK_UN);
			@fclose($fpLock);
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
		$files = array();
		$pkgMetaBasedir = $this->getPackageMetadatasDir();

		foreach ($doc->getXPathValue("root:entry", null, false) as $pkgEntry) {
			// Retrieves needed values
			$pkgName = $doc->getXPathValue("a:name", $pkgEntry);
			$pkgVersion = $doc->getXPathValue("a:version", $pkgEntry);
			$pkgRelease = $doc->getXPathValue("a:release", $pkgEntry);
			$pkgUrl = $doc->getXPathValue("root:link[@a:type='aps']/@href", $pkgEntry);
			$pkgMetaUrl = $doc->getXPathValue("root:link[@a:type='meta']/@href", $pkgEntry);
			$pkgIconUrl = $doc->getXPathValue("root:link[@a:type='icon']/@href", $pkgEntry);

			if ($pkgName == '' || $pkgVersion == '' || $pkgRelease == '' || $pkgUrl == '' || $pkgMetaUrl == '') {
				continue; // Ignore invalid package entry
			}

			// Package metadatas directory
			$pkgMetaDir = "$pkgMetaBasedir/$repoId/$pkgName";

			$pkgCversion = null;
			$pkgCRelease = null;
			if (isset($this->packages[$repoId][$pkgName])) {
				$pkgCversion = $this->packages[$repoId][$pkgName]['version'];
				$pkgCRelease = $this->packages[$repoId][$pkgName]['release'];
			}

			$isKnowVersion = !is_null($pkgCversion);
			$isOutDatedVersion = ($isKnowVersion)
				? (version_compare($pkgCversion, $pkgVersion, '<') || $pkgCRelease < $pkgCversion) : false;

			// Process only if a newer version is available or if there is no valid APP-META.xml, APP-URL or
			// APP-ICON-URL file
			if (
				(!$isKnowVersion || $isOutDatedVersion) ||
				!file_exists("$pkgMetaDir/APP-META.xml") || filesize("$pkgMetaDir/APP-META.xml") == 0 ||
				!file_exists("$pkgMetaDir/APP-URL") || filesize("$pkgMetaDir/APP-URL") == 0 ||
				!file_exists("$pkgMetaDir/APP-ICON-URL") || filesize("$pkgMetaDir/APP-ICON-URL") == 0
			) {
				// Delete out-dated version if any
				if ($isOutDatedVersion) {
					utils_removeDir("$pkgMetaBasedir/$repoId/$pkgName/$pkgCversion");
					exec_query(
						'
							DELETE FROM `aps_packages`
							WHERE `name` = ? AND aps_version = ? AND `version` = ? AND `release` = ?
						',
						array($pkgName, $repoId, $pkgCversion, $pkgCRelease)
					);
				}

				@mkdir($pkgMetaDir, 0750, true); // Create package metadatas directory if needed
				@file_put_contents("$pkgMetaDir/APP-URL", $pkgUrl); // Save package URL
				@file_put_contents("$pkgMetaDir/APP-ICON-URL", $pkgIconUrl); // Save package icon URL
				$files[] = array('src' => $pkgMetaUrl, 'trg' => "$pkgMetaDir/APP-META.xml"); // Download APP-META.xml file
			}
		}

		if(!empty($files)) {
			$this->downloadFiles($files); // Download package APP-META.xml files
		}

		/*
		foreach ($doc->getValue("root:entry", null, false) as $package) {
			$name = $doc->getValue("a:name", $package);
			$version = $doc->getValue("a:version", $package);
			$release = $doc->getValue("a:release", $package);
			$apsVersion = $doc->getValue("a:repository", $package);

			$packageMetadatasDir = $metadatasDir . '/'  . $apsVersion  . '/' .$name;


			$isKnowPackage = (array_key_exists($apsVersion, $this->packages))
				? array_key_exists($name, $this->packages[$apsVersion]) : false;

			$isOutDatedPackage = ($isKnowPackage)
				? version_compare(
					$this->packages[$apsVersion][$name]['version'] . '-' . $this->packages[$apsVersion][$name]['release'],
					$version . '-' . $release,
					'<'
				)
				: false;

			$isMissingMetaFile = (! file_exists($packageMetadatasDir . '/APP-META.xml'));

			if (!$isKnowPackage || $isOutDatedPackage || $isMissingMetaFile) {
				if ($isOutDatedPackage || $isMissingMetaFile) {
					// Remove out-dated package data if any
					utils_removeDir($packageMetadatasDir);
					exec_query('DELETE FROM `aps_packages` WHERE `name` = ? AND aps_version = ?', array(
						$name, $apsVersion
					));
				}

				$this->packages[$apsVersion][$name] = array('version' => $version, 'release' => $release);

				$summary = $doc->getValue("a:summary", $package);
				$category = $doc->getValue("root:category/@term", $package);
				$vendor = $doc->getValue("a:vendor", $package);
				$vendorURI = $doc->getValue("a:vendor_uri", $package);
				$url = $doc->getValue("root:link[@a:type='aps']/@href", $package);
				$iconUrl = $doc->getValue("root:link[@a:type='icon']/@href", $package);
				$metaSrc = $doc->getValue("root:link[@a:type='meta']/@href", $package);
				$cert = $doc->getValue("root:link[@a:type='certificate']/a:level", $package);
				$license = $doc->getValue("root:link[@a:type='eula']/@href", $package);

				// Create package metadatas directory if needed
				@mkdir($packageMetadatasDir, 0750, true);

				// Schedule download of APP-META.xml file
				$metaFiles[] = array('src' => $metaSrc, 'trg' => $packageMetadatasDir . '/APP-META.xml');

				// Schedule download of license file if any
				if($license != '') {
					$metaFiles[] = array('src' => $license, 'trg' => $packageMetadatasDir . '/LICENSE');
				}

				// TODO Schedule download of screenshots if any

				exec_query(
					'
						INSERT INTO aps_packages (
							`name`, `summary`, `version`, `aps_version`, `release`, `category`, `vendor`, `vendor_uri`,
							`url`, `icon_url`, `cert`, `status`
						) VALUES(
							?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
						)
					',
					array(
						$name, $summary, $version, $apsVersion, $release, $category, $vendor, $vendorURI, $url,
						$iconUrl, $cert, 'ok'
					)
				);
			}
		}*/

		//$this->downloadPackageMetaFiles($metaFiles);
		//$this->updatePackageIndex($repoId);
	}

	/**
	 * Update package index by exploring local metadatas directory for the given repository
	 *
	 * @param string $repoId Repository unique identifier (e.g. 1, 1.1, 1.2, .2.0)
	 */
	protected function updatePackageIndex($repoId)
	{
		// TODO
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
				$curlHandle = curl_init($chunk[$i]['src']);
				$fileHandle = fopen($chunk[$i]['trg'], 'wb');

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
}
