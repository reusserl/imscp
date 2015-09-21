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

use PDO;

/**
 * Class Spider
 *
 * @package iMSCP\ApsStandard
 */
class Spider extends ApsStandardAbstract
{
	/**
	 * @var array List known packages
	 */
	protected $packages = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ignore_user_abort(1); // Do not abort on a client disconnection
		set_time_limit(0); // Explore task can take up several minutes to finish

		$stmt = exec_query('SELECT `name`, `version`, `aps_version`, `release` FROM aps_packages');

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$this->packages[$row['aps_version']][$row['name']] = array(
					'version' => $row['version'],
					'release' => $row['release']
				);
			}
		}
	}

	/**
	 * Update package index by exploring APS standard repositories
	 *
	 * TODO: Better error handling
	 *
	 * Return void
	 */
	public function explore()
	{
		try {
			$baseURL = $this->getAPScatalogURL();
			$indexDoc = new Document($baseURL, 'html');

			// Retrieves list of available APS standard repositories
			// See https://doc.apsstandard.org/2.1/portal/cat/browsing/#retrieving-repository-index
			$repos = $indexDoc->getvalue("//a[@class='repository']/@href", null, false);

			foreach ($repos as $repo) {
				$repoPath = $repo->nodeValue;

				// Explore supported APS standard repositories only
				if (in_array(rtrim($repoPath, '/'), $this->apsVersions)) {
					// Discover repository feed path
					// See https://doc.apsstandard.org/2.1/portal/cat/browsing/#discovering-repository-feed
					$repoIndexDoc = new Document($baseURL . '/' . $repoPath, 'html');
					$repoFeedPath = $repoIndexDoc->getValue("//a[@id='feedLink']/@href");

					// Processes the repository feed by chunk of 100 entries per page (we fetch only latest package versions)
					// See https://doc.apsstandard.org/2.1/portal/cat/search/#search-description-arguments
					// FIXME str_replace is a dirty solution
					$repoFeedDoc = new Document($baseURL . str_replace('../', '/', $repoFeedPath) . '?pageSize=100&latest=1');
					$this->processRepository($repoFeedDoc);
					while ($repoFeedPath = $repoFeedDoc->getValue("atom:link[@rel='next']/@href")) {
						$repoFeedDoc = new Document($repoFeedPath);
						$this->processRepository($repoFeedDoc);
					}
				}
			}
		} catch (\Exception $e) {
			if (php_sapi_name() == 'cli') {
				fwrite(STDERR, sprintf("Unexecpted error: %s\n", $e->getMessage()));
				exit(1);
			}

			throw $e;
		}
	}

	/**
	 * Process the given APS repository feed page
	 *
	 * @param Document $repoFeedDoc Document representing APS repository feed page
	 * @return void
	 */
	protected function processRepository(Document $repoFeedDoc)
	{
		$metaFiles = array();

		foreach ($repoFeedDoc->getValue("atom:entry", null, false) as $package) {
			$packageName = $repoFeedDoc->getValue("a:name", $package);
			$packageVersion = $repoFeedDoc->getValue("a:version", $package);
			$packageRelease = $repoFeedDoc->getValue("a:release", $package);
			$packageApsVersion = $repoFeedDoc->getValue("a:repository", $package);

			$isKnowPackage = (array_key_exists($packageApsVersion, $this->packages))
				? array_key_exists($packageName, $this->packages[$packageApsVersion]) : false;
			$isOutDatedPackage = ($isKnowPackage)
				? version_compare(
					$this->packages[$packageApsVersion][$packageName]['version'] . '-' .
					$this->packages[$packageApsVersion][$packageName]['release'],
					$packageVersion . '-' . $packageRelease,
					'<'
				)
				: false;

			if (!$isKnowPackage || $isOutDatedPackage) {
				$packageMetaFile = $this->packageMetadatasDir . '/' . $packageName . '-' . $packageRelease . '.meta.xml';

				if ($isOutDatedPackage) {
					if (file_exists($packageMetaFile)) {
						@unlink($packageMetaFile);
					}

					exec_query('DELETE FROM `aps_packages` WHERE `name` = ?', $packageName);
				}

				$this->packages[$packageApsVersion][$packageName] = array(
					'package_version' => $packageVersion,
					'package_release' => $packageRelease
				);

				$packageSummary = $repoFeedDoc->getValue("a:summary", $package);
				$packageCategory = $repoFeedDoc->getValue("atom:category/@term", $package);
				$packageVendor = $repoFeedDoc->getValue("a:vendor", $package);;
				$packageVendorUri = $repoFeedDoc->getValue("a:vendor_uri", $package);
				$packagePath = 'TODO';
				$packageUrl = $repoFeedDoc->getValue("atom:link[@a:type='aps']/@href", $package);
				$packagesIconUrl = $repoFeedDoc->getValue("atom:link[@a:type='icon']/@href", $package);
				$packageMetaUrl = $repoFeedDoc->getValue("atom:link[@a:type='meta']/@href", $package);
				$packageCert = $repoFeedDoc->getValue("atom:link[@a:type='certificate']/a:level", $package);

				$metaFiles[] = array(
					'src' => $packageMetaUrl,
					'target' => $packageMetaFile
				);

				exec_query(
					'
						INSERT INTO aps_packages (
							`name`, `summary`, `version`, `aps_version`, `release`, `category`, `vendor`, `vendor_uri`,
							`path, url`, `icon_url`, `cert`, `status`
						) VALUES(
							?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
						)
					',
					array(
						$packageName, $packageSummary, $packageVersion, $packageApsVersion, $packageRelease,
						$packageCategory, $packageVendor, $packageVendorUri, $packagePath, $packageUrl, $packagesIconUrl,
						$packageCert, 'ok'
					)
				);
			}
		}

		$this->downloadMetafiles($metaFiles);
	}

	/**
	 * Download META files
	 *
	 * @param array $metaFiles
	 * @return void
	 */
	protected function downloadMetafiles($metaFiles)
	{
		// Download 20 files at time
		$metaFiles = array_chunk($metaFiles, 20);

		foreach ($metaFiles as $metaFileChunk) {
			$fileHandles = array();
			$curlHandles = array();

			$curlMultiHandle = curl_multi_init();

			# Create cURL handles (one per file) and add them to cURL multi handle
			for ($i = 0, $size = count($metaFileChunk); $i < $size; $i++) {
				$curlHandle = curl_init($metaFileChunk[$i]['src']);
				$fileHandle = fopen($metaFileChunk[$i]['target'], 'wb');

				curl_setopt_array($curlHandle, array(
					CURLOPT_BINARYTRANSFER => true,
					CURLOPT_FILE => $fileHandle,
					CURLOPT_TIMEOUT => 240,
					CURLOPT_FAILONERROR => 1,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_SSL_VERIFYHOST => 2,
					CURLOPT_SSL_VERIFYPEER => false
				));

				$curlHandles[$i] = $curlHandle;
				$fileHandles[$i] = $fileHandle;

				curl_multi_add_handle($curlMultiHandle, $curlHandle);
			}

			// Execute
			do {
				curl_multi_exec($curlMultiHandle, $running);
				curl_multi_select($curlMultiHandle);
			} while ($running > 0);

			# Close
			for ($i = 0, $size = count($metaFileChunk); $i < $size; $i++) {
				fclose($fileHandles[$i]);
				curl_multi_remove_handle($curlMultiHandle, $curlHandles[$i]);
				curl_close($curlHandles[$i]);
			}

			curl_multi_close($curlMultiHandle);
		}
	}
}
