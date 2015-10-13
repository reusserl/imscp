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

use iMSCP_Authentication as Auth;
use iMSCP_Exception_Production as ProductionException;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\JsonResponse as Response;
use Symfony\Component\HttpFoundation\Request;
use Zend\ServiceManager\Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ApsAbstractController
 * @package iMSCP\ApsStandard\Controller
 */
abstract class ApsAbstractController implements ServiceLocatorAwareInterface
{
	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var Auth
	 */
	protected $auth;

	/**
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;

	/**
	 * Constructor
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param Auth $auth
	 */
	public function __construct(Request $request, Response $response, Auth $auth)
	{
		$this->request = $request;
		$this->response = $response;
		$this->auth = $auth;
	}

	/**
	 * Handle HTTP request
	 *
	 * @return void
	 */
	public abstract function handleRequest();

	/**
	 * Set service locator
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 * Get service locator
	 *
	 * @return ServiceLocatorInterface
	 */
	public function getServiceLocator()
	{
		return $this->serviceLocator;
	}

	/**
	 * Get request object
	 *
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get response object
	 *
	 * @return Response
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Get authentication service
	 *
	 * @return Auth
	 */
	public function getAuth()
	{
		return $this->auth;
	}

	/**
	 * Get serializer service
	 *
	 * @return Serializer
	 */
	public function getSerializer()
	{
		return $this->getServiceLocator()->get('Serializer');
	}

	/**
	 * Create response from the given exception
	 *
	 * @throws \Exception
	 * @param \Exception $e
	 * @return void
	 */
	public function createResponseFromException(\Exception $e)
	{
		$code = $e->getCode();

		if (!is_int($code) || $code < 100 || $code >= 600) {
			$code = 500;

			if ($this->getAuth()->getIdentity()->admin_type !== 'admin') {
				$e = new ProductionException('', 500, $e);
			}
		}

		$this->getResponse()->setData(array('message' => $e->getMessage()))->setStatusCode($code);
	}
}
