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

use DOMDocument;
use DOMXPath;
use DOMNodeList;
use DOMNode;

/**
 * Class ApsDocument
 * @package iMSCP\ApsStandard
 */
class ApsDocument
{
	/**
	 * @var DOMDocument Associated Document Model Object
	 */
	protected $dom;

	/**
	 * @var DOMXPath Associated Document Object Model XPath
	 */
	protected $xpath;

	/**
	 * @var array List of APS namespaces
	 */
	protected $apsNamespaces = array(
		'apache' => 'apache="http://apstandard.com/ns/1/apache',
		'aspnet' => 'http://apstandard.com/ns/1/aspnet',
		'cgi' => 'http://apstandard.com/ns/1/cgi',
		'db' => 'http://apstandard.com/ns/1/db',
		'dns' => 'http://apstandard.com/ns/1/dns',
		'env' => 'http://apstandard.com/ns/1/environment',
		'h' => 'http://apstandard.com/ns/1/cgi/handlers',
		'l' => 'http://apstandard.com/ns/1/licensing',
		'mail' => 'http://apstandard.com/ns/1/mail',
		'php' => 'http://apstandard.com/ns/1/php',
		'pvc' => 'http://apstandard.com/ns/1/pvc',
		'svc' => 'http://apstandard.com/ns/1/service'
	);

	/**
	 * Constructor
	 *
	 * @param string $path HTML/XML document path
	 * @param string $type OPTIONAL Document type (xml|html), default to 'xml'
	 */
	public function __construct($path, $type = 'xml')
	{
		$this->load($path, $type);
	}

	/**
	 * Get underlying DOMDocument object associated with this document
	 *
	 * @return \DOMDocument
	 */
	public function getDOMDocument()
	{
		return $this->dom;
	}

	/**
	 * Get underlying DOM XPath object associated with this document
	 *
	 * @return DOMXPath
	 */
	public function getXpath()
	{
		return $this->xpath;
	}

	/**
	 * Get document value by executing the givenXPath expression
	 *
	 * @param string $XPathExpression The XPath expression to execute
	 * @param DOMNode $contextNode OPTIONAL Context node
	 * @param bool $firstNodeOnly OPTIONAL Tells whether or not only the first node value must be returned (default: true)
	 * @return DOMNodeList|string
	 */
	public function getXPathValue($XPathExpression, DOMNode $contextNode = null, $firstNodeOnly = true)
	{
		$ret = $this->xpath->query($XPathExpression, $contextNode);
		return $firstNodeOnly ? ($ret->length ? $ret->item(0)->nodeValue : '') : $ret;
	}

	/**
	 * Load the given HTML/XML document
	 *
	 * @param string $path HTML/XML document path
	 * @param string $type OPTIONAL Document type (xml|html), default to 'xml'
	 * @return void
	 */
	protected function load($path, $type = 'xml')
	{
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		@$ret = ($type == 'xml') ? $doc->load($path, LIBXML_PARSEHUGE) : $doc->loadHTMLFile($path);

		if (!$ret) {
			throw new \RuntimeException(sprintf('Could not load HTML/XML document: %s', $php_errormsg));
		}

		// Create associated DOM XPath object
		$xpath = new DOMXPath($doc);

		// Register root namespaces
		foreach ($xpath->query('namespace::*') as $node) {
			$namespaceURI = $node->nodeValue;
			$prefix = $doc->lookupPrefix($namespaceURI);

			if ($prefix == '') {
				$prefix = 'root';
			}

			if (!$xpath->registerNamespace($prefix, $namespaceURI)) {
				throw new \RuntimeException(sprintf(
					"Could not register the '%s' namespace with '%s' as prefix", $namespaceURI, $prefix
				));
			}
		}

		// Register APS namespaces
		foreach ($this->apsNamespaces as $prefix => $namespaceURI) {
			if (!$xpath->registerNamespace($prefix, $namespaceURI)) {
				throw new \RuntimeException(sprintf(
					"Could not register the '%s' namespace with '%s' as prefix", $namespaceURI, $prefix
				));
			}
		}

		$this->dom = $doc;
		$this->xpath = $xpath;
	}
}
