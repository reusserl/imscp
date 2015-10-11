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
 * Class ApsDocument
 * @package iMSCP\ApsStandard
 */
class ApsDocument
{
	/**
	 * @var \DOMDocument Associated Document Model Object
	 */
	protected $dom;

	/**
	 * @var \DOMXPath Associated Document Object Model XPath
	 */
	protected $xpath;

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
	 * @return \DOMXPath
	 */
	public function getXpath()
	{
		return $this->xpath;
	}

	/**
	 * Get document value by executing the givenXPath expression
	 *
	 * @param string $XPathExpression The XPath expression to execute
	 * @param \DOMNode $contextNode OPTIONAL Context node
	 * @param bool $asString OPTIONAL Tells whether or not only the first node value must be returned (default: true)
	 * @return \DOMNodeList|string
	 */
	public function getXPathValue($XPathExpression, \DOMNode $contextNode = null, $asString = true)
	{
		$ret = $this->xpath->query($XPathExpression, $contextNode);
		return ($asString) ? (($ret->length) ? $ret->item(0)->nodeValue : '') : $ret;
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
		$doc = new \DOMDocument();
		@$ret = ($type == 'xml') ? $doc->load($path, LIBXML_PARSEHUGE) : $doc->loadHTMLFile($path);

		if (!$ret) {
			throw new \RuntimeException(tr(
				"Runtime error: %s\n", tr('Could not load HTML/XML document: %s', $php_errormsg)
			));
		}

		// Create associated DOM XPath object
		$xpath = new \DOMXPath($doc);

		// Set namespaces
		foreach ($xpath->query('namespace::*') as $node) {
			$prefix = $doc->lookupPrefix($node->nodeValue);

			if ($prefix == '') {
				$prefix = 'root';
			}

			if (!$xpath->registerNamespace($prefix, $node->nodeValue)) {
				throw new \RuntimeException(tr(
					"Runtime error: %s\n",
					tr("Could not register '%s' XPath namespace with '%s' as prefix", $prefix, $node->nodeValue)
				));
			}
		}

		$this->dom = $doc;
		$this->xpath = $xpath;
	}
}
