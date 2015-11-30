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

use Symfony\Component\HttpFoundation\Request;
use Zend\Cache\Storage\Adapter\Filesystem;
use Zend\Cache\StorageFactory;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class TranslatorServiceFactory
 * @package iMSCP\Core\Service
 */
class TranslatorServiceFactory implements FactoryInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		$systemConfig = $serviceLocator->get('SystemConfig');

		/** @var Request $request */
		//$request = $serviceLocator->get('Request');


		/*
		if (PHP_SAPI != 'cli') {
			$lang = Registry::set('user_def_lang', isset($_SESSION['user_def_lang'])
				? $_SESSION['user_def_lang']
				: (isset($config['USER_INITIAL_LANG']) ? $config['USER_INITIAL_LANG'] : 'auto')
			);

			if (Locale::isLocale($lang)) {
				$locale = new Locale($lang);

				if ($lang == 'auto') {
					$locale->setLocale('en_GB');
					$browser = $locale->getBrowser();

					arsort($browser);
					foreach ($browser as $language => $quality) {
						if (file_exists(sprintf($trFilePathPattern, $language, $language))) {
							$locale->setLocale($language);
							break;
						}
					}
				} elseif (!file_exists(sprintf($trFilePathPattern, $locale, $locale))) {
					$locale->setLocale('en_GB');
				}
			} else {
				$locale = new Locale('en_GB');
			}
		} else {
			$locale = new Locale('en_GB');
		}
		*/

		/** @var Filesystem $cache */
		$cache = StorageFactory::factory([
			'adapter' => [
				'name' => $systemConfig['DEVMODE'] ? 'Filesystem' : 'Apc', // TODO only if available
				'options' => [
					'cache_dir' => CACHE_PATH . '/translations',
					'ttl' => 0, // Translation cache is never flushed automatically
					'namespace' => 'iMSCP_Translations'
				],
			],
			'plugins' => [
				[
					'name' => 'serializer',
					'options' => []
				],
				'exception_handler' => [
					'throw_exceptions' => true
				]
			]
		]);

		// Setup primary translator for iMSCP core translations
		$translator = Translator::factory([
			'locale' => '',
			'translation_file_patterns' => [
				'type' => 'gettext',
				'base_dir' => $systemConfig['GUI_ROOT_DIR'] . '/i18n/locales',
				'pattern' => '%s/LC_MESSAGES/%s.mo',
				'text_domain' => 'iMSCP'
			],

		]);

		if ($systemConfig['DEBUG']) {
			$cache->clearByNamespace('translations');
		} else {
			$translator->setCache($cache);
		}

		return $translator;
	}
}
