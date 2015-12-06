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

namespace iMSCP\Core\Service;

use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\Http\Request;
use Zend\Navigation\Navigation;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class NavigationFactory
 * @package iMSCP\Core\Service
 */
class NavigationFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Navigation($this->getPages($serviceLocator));
    }

    /**
     * Get nagivation name
     *
     * @return string
     */
    protected function getName()
    {
        return $_SESSION['user_type'];
    }

    /**
     * Get pages
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return array
     * @throws \Zend\Navigation\Exception\InvalidArgumentException
     */
    protected function getPages(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        if (!isset($config['navigation'])) {
            throw new \InvalidArgumentException('Could not find navigation configuration key');
        }

        if (!isset($config['navigation'][$this->getName()])) {
            throw new \InvalidArgumentException(sprintf(
                'Failed to find a navigation container by the name "%s"', $this->getName()
            ));
        }

        return $this->preparePages($serviceLocator, $this->getPagesFromConfig($config['navigation'][$this->getName()]));
    }

    /**
     * Prepare pages
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param array|Config $pages
     * @return null|array
     * @throws \Zend\Navigation\Exception\InvalidArgumentException
     */
    protected function preparePages(ServiceLocatorInterface $serviceLocator, $pages)
    {
        $request = $serviceLocator->get('Request');

        if (!$request instanceof Request) {
            return $pages;
        }

        return $this->injectRequestObject($pages, $request);
    }

    /**
     * Get pages from configuration
     *
     * @param string|Config|array $config
     * @return array|null|Config
     * @throws \Zend\Navigation\Exception\InvalidArgumentException
     */
    protected function getPagesFromConfig($config)
    {
        if (is_string($config)) {
            if (!file_exists($config)) {
                throw new \InvalidArgumentException(sprintf('Config was a string but file "%s" does not exist', $config));
            }

            $config = Factory::fromFile($config);
        } elseif ($config instanceof Config) {
            $config = $config->toArray();
        } elseif (!is_array($config)) {
            throw new \InvalidArgumentException('Invalid input, expected array, filename, or Zend\Config object');
        }

        return $config;
    }

    /**
     * Inject request object into pages
     *
     * @param array $pages
     * @param Request $request
     * @return array
     */
    protected function injectRequestObject(array $pages, Request $request)
    {
        foreach ($pages as &$page) {
            $page['request'] = $request;

            if (isset($page['pages'])) {
                $page['pages'] = $this->injectRequestObject($page['pages'], $request);
            }
        }

        return $pages;
    }
}
