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

namespace iMSCP\ApsStandard\Service;

use iMSCP\ApsStandard\ApsStandardAbstract;
use iMSCP_Authentication as Authentication;
use iMSCP_Database as Database;
use iMSCP_Events_Aggregator as EventManager;

/**
 * Class PackageService
 * @package iMSCP\ApsStandard\Service
 */
abstract class ServiceAbstract extends ApsStandardAbstract
{
	/**
	 * @var \stdClass $identity User identity
	 */
	protected $identity;

	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/**
	 * @var \PDO
	 */
	protected $db;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->eventManager = EventManager::getInstance();
		$this->identity = Authentication::getInstance()->getIdentity();
		$this->db = Database::getRawInstance();
	}
}
