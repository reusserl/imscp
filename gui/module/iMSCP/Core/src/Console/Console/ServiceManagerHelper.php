<?php

namespace iMSCP\Core\Console;

use Symfony\Component\Console\Helper\Helper;
use Zend\ServiceManager\ServiceManager;

/**
 * Class ServiceManagerHelper
 * @package iMSCP\Core\Console
 */
class ServiceManagerHelper extends Helper
{
	/**
	 * @var ServiceManager
	 */
	protected $serviceManager;

	/**
	 * Constructor
	 *
	 * @param ServiceManager $serviceManager
	 */
	public function __construct(ServiceManager $serviceManager)
	{
		$this->serviceManager = $serviceManager;
	}

	/**
	 * Retrieves the service manager
	 *
	 * @return ServiceManager $serviceManager
	 */
	public function getServiceManager()
	{
		return $this->serviceManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'servicemanager';
	}
}
