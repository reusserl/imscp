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

namespace iMSCP\ApsStandard\Resource;

use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ApsPageableResourceCollection
 *
 * @package iMSCP\ApsStandard\Resource
 * @JMS\ExclusionPolicy("all")
 */
class ApsPageableResourceCollection
{
	/**
	 * @var Paginator
	 */
	protected $paginator;

	/**
	 * @var array Notifications
	 */
	protected $notifications;

	/**
	 * Constructor
	 *
	 * @param Paginator $paginator
	 * @param array $notifications OPTIONAL Array containing notifications
	 */
	public function __construct(Paginator $paginator, array $notifications = array())
	{
		$this->paginator = $paginator;
		$this->notifications = $notifications;
	}

	/**
	 * Get resources
	 *
	 * @return array
	 */
	public function getResources()
	{
		return $this->paginator->getIterator()->getArrayCopy();
	}

	/**
	 * Get resource count
	 *
	 * @return int|number
	 */
	public function getResourceCount()
	{
		return $this->paginator->count();
	}

	/**
	 * Get pageable collection
	 *
	 * @JMS\VirtualProperty()
	 * @JMS\Inline()
	 * @return array
	 */
	public function getPageableCollection()
	{
		return array(
			'resources' => $this->getResources(),
			'resourceCount' => $this->getResourceCount(),
			'notifications' =>  $this->notifications
		);
	}
}
