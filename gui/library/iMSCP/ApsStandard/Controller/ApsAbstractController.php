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

namespace iMSCP\ApsStandard\Controller;

use iMSCP\Validate\ValidatorProviderInterface;
use JMS\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

/**
 * Class ApsAbstractController
 * @package iMSCP\ApsStandard\Controller
 */
abstract class ApsAbstractController implements ValidatorProviderInterface
{
	/**
	 * @var \JMS\Serializer\Serializer
	 */
	protected $serialiser;

	/**
	 * Constructor
	 *
	 * @param Serializer $serializer
	 */
	public function __construct(Serializer $serializer)
	{
		$this->serialiser = $serializer;
	}

	/**
	 * Handle HTTP request
	 *
	 * @return void
	 */
	abstract function handleRequest();

	/**
	 * {@inheritdoc}
	 */
	public function getValidator()
	{
		return Validation::createValidatorBuilder()->addMethodMapping('loadValidationMetadata')->getValidator();
	}

	/**
	 * Get serializer
	 *
	 * @return \JMS\Serializer\Serializer
	 */
	protected function getSerializer()
	{
		return $this->serialiser;
	}

	/**
	 * Send Json response
	 *
	 * @param int $statusCode HTTP status code
	 * @param array|string $data JSON data
	 * @return void
	 */
	protected function sendResponse($statusCode = 200, $data = '')
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		switch ($statusCode) {
			case 200:
				header('Status: 200 OK');
				break;
			case 201:
				header('Status: 201 Created');
				break;
			case 202:
				header('Status: 202 Accepted');
				break;
			case 204:
				header('Status: 204 No Content');
				break;
			case 400:
				header('Status: 400 Bad Request');
				break;
			case 403:
				header('Status: 403 Forbidden');
				break;
			case 404:
				header('Status: 404 Not Found');
				break;
			case 409:
				header('Status: 409 Conflict');
				break;
			case 500:
				header('Status: 500 Internal Server Error');
				break;
			case 501:
				header('Status: 501 Not Implemented');
				break;
			default:
				header('Status: 200 OK');
		}

		exit($this->getSerializer()->serialize($data, 'json'));
	}
}
