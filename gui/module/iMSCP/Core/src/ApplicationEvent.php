<?php

namespace iMSCP\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zend\EventManager\Event;

/**
 * Class ApplicationEvent
 * @package iMSCP\Events
 */
class ApplicationEvent extends Event
{
	const EVENT_BOOTSTRAP = 'bootstrap';

	/**
	 * @var \iMSCP\Core\ApplicationInterface
	 */
	protected $application;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var mixed
	 */
	protected $result;

	/**
	 * Set application instance
	 *
	 * @param ApplicationInterface $application
	 * @return $this
	 */
	public function setApplication(ApplicationInterface $application)
	{
		$this->setParam('application', $application);
		$this->application = $application;
		return $this;
	}

	/**
	 * Get application instance
	 *
	 * @return ApplicationInterface
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * Set request Object
	 *
	 * @param Request $request
	 * @return $this
	 */
	public function setRequest(Request $request)
	{
		$this->setParam('request', $request);
		$this->request = $request;
		return $this;
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
	 * @param Response $response
	 * @return $this
	 */
	public function setResponse(Response $response)
	{
		$this->setParam('response', $response);
		$this->response = $response;
		return $this;
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
	 * Set result
	 *
	 * @param mixed $result
	 * @return $this
	 */
	public function setResult($result)
	{
		$this->setParam('__RESULT__', $result);
		$this->result = $result;
		return $this;
	}

	/**
	 * Get result
	 *
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}
}
