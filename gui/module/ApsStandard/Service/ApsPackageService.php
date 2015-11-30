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

use Doctrine\ORM\Tools\Pagination\Paginator;
use iMSCP\ApsStandard\ApsDocument;
use iMSCP\ApsStandard\Entity\ApsPackage;
use iMSCP\ApsStandard\Entity\ApsPackageDetails;
use iMSCP\ApsStandard\Resource\ApsPageableResourceCollection;
use JMS\Serializer\Serializer;
use Zend_Session as SessionHandler;

/**
 * Class ApsPackageService
 * @package iMSCP\ApsStandard\Service
 */
class ApsPackageService extends ApsAbstractService
{
	const PACKAGE_ENTITY_CLASS = 'iMSCP\\ApsStandard\\Entity\\ApsPackage';

	/**
	 * Get package categories
	 *
	 * @return array
	 */
	public function getPackageCategories()
	{
		$qb = $this->getEntityManager()->createQueryBuilder()
			->select('p.category')->distinct()->from('Aps:ApsPackage', 'p');

		if ($this->getAuth()->getIdentity()->admin_type === 'admin') {
			$qb->where($qb->expr()->in('p.status', ['locked', 'unlocked']));
		} else {
			$qb->where($qb->expr()->eq('p.status', $qb->expr()->literal('unlocked')));
		}

		return $qb->getQuery()->useResultCache(true)->setResultCacheId('aps_package_categories')->execute();
	}

	/**
	 * Get packages
	 *
	 * @param int $offset The first result to return
	 * @param int $limit The maximum number of results to return
	 * @param array $criteria Filter criteria
	 * @return ApsPageableResourceCollection
	 */
	public function getPackages($offset, $limit, array $criteria = [])
	{
		$qb = $this->getEntityManager()->createQueryBuilder()->select('p')->from('Aps:ApsPackage', 'p');

		if ($this->getAuth()->getIdentity()->admin_type === 'admin') {
			$qb->where($qb->expr()->in('p.status', ['locked', 'unlocked']));
		} else {
			$qb->where($qb->expr()->eq('p.status', $qb->expr()->literal('unlocked')));
		}

		if (isset($criteria['category'])) {
			$qb->andWhere($qb->expr()->eq('p.category', ':category'))->setParameter('category', $criteria['category']);
		}

		if (isset($criteria['globalSearch'])) {
			$or = $qb->expr()->orX();
			foreach (['summary', 'version', 'release', 'apsVersion', 'vendor', 'cert'] as $column) {
				$or->add($qb->expr()->like("p.$column", ":$column"));
				$qb->setParameter($column, "%{$criteria['globalSearch']}%");
			}

			$qb->andWhere($or);
		}

		$qb
			->setFirstResult($offset)
			->setMaxResults($limit)
			->setCacheable(true)
			->setCacheRegion('aps_packages');

		return new ApsPageableResourceCollection(new Paginator($qb));
	}

	/**
	 * Get package
	 *
	 * @throws \Exception
	 * @param int $packageId Package identifier
	 * @param array $criterias Criterias
	 * @return ApsPackageDetails
	 */
	public function getPackage($packageId, $criterias)
	{
		$package = $this->getEntityManager()->getRepository('Aps:ApsPackage')->find($packageId, $criterias);

		if (!$package) {
			throw new \Exception(tr('Package not found.'), 404);
		}

		$metadataDir = $this->getPackageMetadataDir() . '/' . $package->getApsVersion() . '/' . $package->getName();
		$metaFile = $metadataDir . '/APP-META.xml';

		if (!file_exists($metaFile) || filesize($metaFile) == 0) {
			throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $metaFile));
		}

		$doc = new ApsDocument($metaFile);
		$packageDetails = (new ApsPackageDetails())
			->setDescription(str_replace(['  ', "\n", "\t"], '', trim($doc->getXPathValue('//root:description'))))
			->setPackager(
				$doc->getXPathValue('//root:packager/root:name') ?:
					parse_url($doc->getXPathValue('//root:package-homepage'), PHP_URL_HOST) ?: tr('Unknown')
			);

		if ($doc->getXPathValue("//root:license")) {
			$licenseSrc = $doc->getXPathValue("//root:license/text/url");
			$licenseSrc = $licenseSrc ?: $metadataDir . '/LICENSE';

			if (($text = @file_get_contents($licenseSrc)) !== false) {
				if (($encText = @iconv(@mb_detect_encoding($text, mb_detect_order(), true), 'UTF-8', $text)) === false) {
					$encText = utf8_encode($text);
				}

				$packageDetails->setLicense($encText);
			}
		}

		return $packageDetails;
	}

	/**
	 * Update package
	 *
	 * @param ApsPackage $package
	 * @return void
	 */
	public function updatePackage(ApsPackage $package)
	{
		$results = $this->getEventManager()->dispatch('onUpdateApsPackageStatus', [
			'package' => $package, 'context' => $this]
		);

		if($results->last() !== false) {
			if (count($this->getValidator()->validate($package))) {
				throw new \DomainException(tr('Invalid package.'), 400);
			}

			$this->getEntityManager()->flush();
		}
	}

	/**
	 * Update package index
	 *
	 * @return void
	 */
	public function updatePackageIndex()
	{
		$results = $this->getEventManager()->dispatch('onUpdateApsPackageIndex', ['context' => $this]);

		if($results !== false) {
			SessionHandler::writeClose(); // Not doing this would block the client for another request...
			$this->getServiceLocator()->get('ApsSpiderService')->exploreCatalog();
			$this->flushResultCacheEntries();
		}
	}

	/**
	 * Get serializer service
	 *
	 * @return Serializer
	 */
	protected function getSerializer()
	{
		return $this->getServiceLocator()->get('Serializer');
	}

	/**
	 * Flush any APS package result cache entries
	 */
	protected function flushResultCacheEntries()
	{
		$em = $this->getEntityManager();
		$em->getConfiguration()->getResultCacheImpl()->delete('aps_package_categories');
		$em->getCache()->evictQueryRegion('aps_packages');
		$em->getCache()->evictEntityRegion(self::PACKAGE_ENTITY_CLASS);
	}
}
