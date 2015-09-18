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
use DOMNode;
use DOMNodeList;
use DOMXPath;

/**
 * Class Document
 * @package iMSCP\ApsStandard
 */
class Document
{
	/**
	 * @var DOMDocument Associated DOMDocument object
	 */
	protected $DOMdocument;

	/**
	 * @var DOMXPath Associated DOMXPath object
	 */
	protected $DOMXPath;

	/**
	 * Constructor
	 *
	 * @param string $path HTML/XML document path
	 * @param string $type OPTIONAL Document type (xml|html), default to 'xml'
	 */
	public function __construct($path, $type = 'xml')
	{
		$this->DOMdocument = new DOMDocument();
		$this->load($path, $type);
	}

	/**
	 * Get underlying DOMDocument object associated with thid document
	 *
	 * @return DOMDocument
	 */
	public function getDOMDocument()
	{
		return $this->DOMdocument;
	}

	/**
	 * Get underlying DOMXPath object associated with thid document
	 *
	 * @return DOMXPath
	 */
	public function getDOMXPath()
	{
		return $this->DOMXPath;
	}

	/**
	 * Get document value by executing the givenXPath expression
	 *
	 * @param string $XPathExpression The XPath expression to execute
	 * @param DOMNode $contextNode OPTIONAL Context node
	 * @param bool $asString OPTIONAL Weither value must be returned as string (default: true)
	 * @return DOMNodeList|string
	 */
	public function getValue($XPathExpression, DOMNode $contextNode = null, $asString = true)
	{
		$ret = $this->DOMXPath->query($XPathExpression, $contextNode);
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
		$doc = $this->DOMdocument;
		$ret = ($type == 'xml') ? $doc->load($path, LIBXML_PARSEHUGE) : $doc->loadHTMLFile($path);

		if (!$ret) {
			throw new \RuntimeException(sprintf('Could not load the %s document', $path));
		}

		$xpath = new DOMXPath($doc);

		foreach ($xpath->query('namespace::*') as $node) {
			$prefix = $doc->lookupPrefix($node->nodeValue);

			if ($prefix == '') {
				$prefix = 'atom'; // Assume atom as default prefix
			}

			if (!$xpath->registerNamespace($prefix, $node->nodeValue)) {
				throw new \RuntimeException(sprintf(
					"Could not register '%s' XPath namespace with '%s' as prefix", $prefix, $node->nodeValue
				));
			}
		}

		$this->DOMXPath = $xpath;
	}
}
