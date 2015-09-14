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

use DOMNode;
use DOMDocument;
use DOMXPath;
use DOMNodeList;

/**
 * Class ApsStandard
 * @package iMSCP\ApsStandard
 */
abstract class ApsStandardAbstract
{
	/**
	 * @var array List of supported APS versionns
	 */
	protected $apsVersions = array(
		'1',
		'1.1',
		'1.2'
	);

	/**
	 * @var string APS catalog URL
	 **/
	protected $apsCatalogURL = 'http://apscatalog.com';

	/**
	 * @var string APS packages metadatas directory
	 */
	protected $packageMetadatasDir;

	/**
	 * @var string APS packages directory
	 */
	protected $packagesDir;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->packagesDir = GUI_ROOT_DIR . '/data/persistent/aps/packages';
		$this->packageMetadatasDir = GUI_ROOT_DIR . '/data/persistent/aps/package_metadatas';
	}

	/**
	 * Get APS catalog URL
	 *
	 * @return string
	 */
	public function getAPScatalogURL()
	{
		return $this->apsCatalogURL;
	}

	/**
	 * Set APS catalog URL
	 *
	 * @param string $url URL
	 * @return void
	 */
	public function setAPScatalogURL($url)
	{
		$this->apsCatalogURL = (string)$url;
	}

	/**
	 * Load the given HTML/XML document
	 *
	 * @throw \RuntimeException
	 * @param string $path HTML/XML document path
	 * @param string $type OPTIONAL Document type (xml|html), default to 'xml'
	 * @return DOMDocument
	 */
	protected function loadAPSdocument($path, $type = 'xml')
	{
		$doc = new DOMDocument();
		$ret = ($type == 'xml') ? $doc->load($path, LIBXML_PARSEHUGE) : $doc->loadHTMLFile($path);

		if (!$ret) {
			throw new \RuntimeException(sprintf('Could not load the %s APS document', $path));
		}

		return $doc;
	}

	/**
	 * Get an APS document value by executing the givenXPath expression on the given APS document
	 *
	 * @param DOMDocument $doc HTML or XML document
	 * @param string $xPathExpression The XPath expression to execute
	 * @param DOMNode $contextNode OPTIONAL Context node
	 * @param bool|false $asString Weither value must be returned as string (node value of first item)
	 * @return DOMNodeList|string
	 */
	public function getAPSvalue(DOMDocument $doc, $xPathExpression, DOMNode $contextNode = null, $asString = false)
	{
		$xPath = new DOMXPath($doc);

		foreach ($xPath->query('namespace::*') as $node) {
			$prefix = $doc->lookupPrefix($node->nodeValue);

			if ($prefix == '') {
				$prefix = 'atom'; // Assume atom for default namespace
			}

			if (!$xPath->registerNamespace($prefix, $node->nodeValue)) {
				throw new \RuntimeException(sprintf(
					"Could not register '%s' XPath namespace with '%s' as prefix", $prefix, $node->nodeValue
				));
			}
		}

		$ret = $xPath->query($xPathExpression, $contextNode);

		if ($asString) {
			$ret = ($ret->length) ? $ret->item(0)->nodeValue : '';
		}

		return $ret;
	}
}
