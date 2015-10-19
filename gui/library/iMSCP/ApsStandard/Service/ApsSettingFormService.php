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

use Doctrine\ORM\EntityManager;
use iMSCP_Authentication as Auth;
use iMSCP\ApsStandard\ApsDocument;
use iMSCP\ApsStandard\Entity\ApsPackage;
use iMSCP_Registry as Registry;

/**
 * Class ApsSettingFormService
 * @package iMSCP\ApsStandard
 */
class ApsSettingFormService extends AbstractApsService
{
	/**
	 * @var Auth
	 */
	protected $authService;

	/**
	 * Constructor
	 * @param EntityManager $entityManager
	 * @param Auth $auth
	 */
	public function __construct(EntityManager $entityManager, Auth $auth)
	{
		parent::__construct($entityManager);
		$this->authService = $auth;
	}

	/**
	 * Get authentication service
	 *
	 * @return Auth
	 */
	public function getAuthService()
	{
		return $this->authService;
	}

	/**
	 * Build schema form for the given package identifier
	 *
	 * @param int $id Package identitier
	 * @return array
	 */
	public function buildSchema($id)
	{
		/** @var ApsPackage $package */
		$package = $this->getServiceLocator()->get('ApsPackageService')->getPackage($id);
		$meta = $this->getMetadataDir() . '/' . $package->getApsVersion() . '/' . $package->getName() . '/APP-META.xml';

		if (!file_exists($meta) || filesize($meta) == 0) {
			throw new \RuntimeException(tr('The %s package META file is missing or invalid.', $meta));
		}

		$doc = new ApsDocument($meta);

		// Installation target settings
		$options = $this->getDomainList();
		$fieldsets = array(array('legend' => tr('Installation target'), 'fields' => array(
			array(
				'id' => 'base_url_host',
				'tpl' => 'enum',
				'label' => tr('Target domain'),
				'value' => $options[0]['value'],
				'tooltip' => tr('The domain under which the application must be installed.'),
				'options' => $options,
			),
			array(
				'id' => 'base_url_path',
				'tpl' => 'string',
				'type' => 'text',
				'label' => tr('Target folder'),
				'tooltip' => tr('An optional subfolder in which the application must be installed.'),
				'value' => '/',
				'min_length' => 0,
				'max_length' => 255
			)
		)));

		// Database settings (if required)
		if ($doc->getXPathValue('//db:id')) {
			$config = Registry::get('config');
			if ($config['PASSWD_CHARS'] < 6) {
				$config['PASSWD_CHARS'] = 6;
			} elseif ($config['PASSWD_CHARS'] > 30) {
				$config['PASSWD_CHARS'] = 30;
			}

			$fieldsets[] = array('legend' => tr('Database'), 'settings' => array(
				array(
					'id' => 'db_password',
					'tpl' => 'string',
					'type' => 'password',
					'label' => tr('Password'),
					'value' => '',
					'regexp' => '^\x21-\x7e$',
					'min_length' => 6,
					'max_length' => $config['PASSWD_CHARS']
				),
				array(
					'id' => 'db_password_c',
					'tpl' => 'string',
					'type' => 'password',
					'label' => tr('Password confirmation'),
					'value' => '',
					'regexp' => '^\x21-\x7e$',
					'min_length' => 6,
					'max_length' => $config['PASSWD_CHARS']
				)
			));
		}

		// application settings
		$fieldsets = array_merge($fieldsets, $this->getFieldsetsFromMetaFile($doc));

		return array(
			'id' => $id,
			'fieldsets' => $fieldsets
		);
	}

	/**
	 * Get form fieldsets from metadata file
	 *
	 * @param ApsDocument $doc
	 * @return array
	 */
	protected function getFieldsetsFromMetaFile($doc)
	{
		$intLang = str_replace('_', '-', $_SESSION['user_def_lang']);

		// Retrieve all setting groups from the package metadata file
		$fieldsets = array();
		foreach ($doc->getXPathValue('//root:settings/root:group', null, false) as $group) {
			// Retrieve all settings in current setting group (or subgroup)
			$fieldset = array();
			foreach ($doc->getXPathValue('(root:group/root:setting|root:setting)', $group, false) as $setting) {
				$field = array();
				$settingType = $doc->getXPathValue('@type', $setting);

				switch ($settingType) {
					case 'enum':
						$field['tpl'] = 'select';
						$options = array();
						foreach ($doc->getXPathValue('root:choice', $setting, false) as $choice) {
							$options[] = array(
								'name' => $doc->getXPathValue('root:name', $choice),
								'value' => strval($doc->getXPathValue('@id', $choice))
							);
						}
						$field['options'] = $options;
						$field['value'] = $doc->getXPathValue('@default-value', $setting) ?: $options[0]['value'];
						break;
					case 'boolean':
						$field['tpl'] = 'boolean';
						$field['value'] = $doc->getXPathValue('@default-value', $setting);
						break;
					default:
						$field['tpl'] = 'string';

						if (in_array($settingType, array('string', 'integer', 'float', 'domain-name'))) {
							$field['type'] = 'text';
						} else { // email, password
							$field['type'] = $settingType;
						}

						$field['value'] = $doc->getXPathValue('@default-value', $setting);
				}

				$field['id'] = $doc->getXPathValue('@id', $setting);
				$field['label'] = $doc->getXPathValue("root:name[@xml:lang='$intLang']/text()", $setting) ?:
					$doc->getXPathValue("root:name/text()", $setting);
				$field['regexp'] = $doc->getXPathValue('@regex', $setting);
				$field['min_length'] = $doc->getXPathValue('@min-length', $setting) ?: 0;
				$field['max_length'] = $doc->getXPathValue('@max-length', $setting);
				$fieldset[] = $field;
			}

			$fieldsets[] = array(
				'legend' => $doc->getXPathValue("root:name[@xml:lang='$intLang']/text()", $group) ?:
					$doc->getXPathValue("root:name/text()", $group) ?: tr('Other settings'),
				'fields' => $fieldset
			);
		}

		return $fieldsets;
	}

	/**
	 * Get domain list
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Exception
	 * @return array
	 */
	protected function getDomainList()
	{
		$mainDmnProps = get_domain_default_props($this->getAuthService()->getIdentity()->admin_id);
		$domainsList = array(array(
			'name' => $mainDmnProps['domain_name'],
			'value' => $mainDmnProps['domain_id'] . '_' . 'dmn'
		));

		$stmt = $this->entityManager->getConnection()->prepare(
			"
				SELECT
					CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `name`,
					CONCAT(`t1`.`subdomain_id`, '_sub') AS `value`
				FROM
					`subdomain` AS `t1`
				INNER JOIN
					`domain` AS `t2` USING(`domain_id`)
				WHERE
					`t1`.`domain_id` = :domain_id
				AND
					`t1`.`subdomain_status` = :status_ok
				UNION
				SELECT
					`alias_name`,
					CONCAT(`alias_id`, '_als')
				FROM
					`domain_aliasses`
				WHERE
					`domain_id` = :domain_id
				AND
					`alias_status` = :status_ok
				UNION
				SELECT
					CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`),
					CONCAT(`t1`.`subdomain_alias_id`, '_alssub')
				FROM
					`subdomain_alias` AS `t1`
				INNER JOIN
					`domain_aliasses` AS `t2` USING(`alias_id`)
				WHERE
					`t2`.`domain_id` = :domain_id
				AND
					`subdomain_alias_status` = :status_ok
		");

		$stmt->execute(array('domain_id' => $mainDmnProps['domain_id'], 'status_ok' => 'ok'));
		if ($stmt->rowCount()) {
			$domainsList = array_merge($domainsList, $stmt->fetchAll(\PDO::FETCH_ASSOC));
			usort($domainsList, function ($a, $b) {
				return strnatcmp(decode_idna($a['name']), decode_idna($b['name']));
			});
		}

		return $domainsList;
	}
}
