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

namespace iMSCP\ApsStandard\Model;

/**
 * Class PackageCollection
 * @package iMSCP\ApsStandard\Model
 */
class PackageCollection extends CollectionAbstract
{
	/**
	 * Get total packages in that collection
	 *
	 * @return int Package count
	 */
	public function getTotalPackages()
	{
		return count($this->models);
	}

	/**
	 * {@inheritDoc}
	 */
	public function hydrate(array $values)
	{
		foreach ($values as $entityData) {
			$entity = new Package();
			$this->addEntity($entity->hydrate($entityData));
		}

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function extract()
	{
		$data['packages'] = parent::extract();
		$data['total_packages'] = $this->getTotalPackages();
		return $data;
	}
}
