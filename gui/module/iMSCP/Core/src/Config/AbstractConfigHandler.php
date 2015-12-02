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

namespace iMSCP\Core\Config;

/**
 * Class AbstractConfigHandler
 * @package iMSCP\Core\Config
 */
class AbstractConfigHandler implements \ArrayAccess, \iterator
{
	/**
	 * @var array Configuration parameters
	 */
	protected $parameters = [];

	/**
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value)
	{
		$this->parameters[$offset] = $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) {
			throw new \InvalidArgumentException("Configuration variable `$offset` is missing.");
		}

		return $this->parameters[$offset];
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetExists($offset)
	{
		return key($this->parameters) !== null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset)
	{
		unset($this->parameters[$offset]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function current()
	{
		return current($this->parameters);
	}

	/**
	 * {@inheritdoc}
	 */
	public function next()
	{
		next($this->parameters);
	}

	/**
	 * {@inheritdoc}
	 */
	public function key()
	{
		return key($this->parameters);
	}

	/**
	 * {@inheritdoc}
	 */
	public function valid()
	{
		return array_key_exists(key($this->parameters), $this->parameters);
	}

	/**
	 * {@inheritdoc}
	 */
	public function rewind()
	{
		reset($this->parameters);
	}

	/**
	 * Return array representation of the configuration object
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->parameters;
	}
}
