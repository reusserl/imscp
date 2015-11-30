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

namespace iMSCP\Core\Console;

use Doctrine\DBAL\Tools\Console\Command\ImportCommand;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand;
use Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand;
use Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;
use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use iMSCP\Core\Translation\BuildLanguageIndexCommand;
use iMSCP\Core\Updater\UpdateDatabaseCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Zend\ServiceManager\ServiceManager;

/**
 * Class ConsoleRunner
 * @package iMSCP\Tools\Console
 */
class ConsoleRunner
{
	/**
	 * Create a Symfony Console HelperSet
	 *
	 * @param ServiceManager $serviceManager
	 * @return HelperSet
	 */
	public static function createHelperSet(ServiceManager $serviceManager)
	{
		/** @var EntityManager $em */
		$em = $serviceManager->get('EntityManager');

		return new HelperSet([
			'sm' => new ServiceManagerHelper($serviceManager),
			'db' => new ConnectionHelper($em->getConnection()),
			'em' => new EntityManagerHelper($em)
		]);
	}

	/**
	 * Runs console with the given helperset
	 *
	 * @param \Symfony\Component\Console\Helper\HelperSet $helperSet
	 * @param \Symfony\Component\Console\Command\Command[] $commands
	 * @return void
	 */
	public static function run(HelperSet $helperSet, $commands = [])
	{
		$cli = self::createApplication($helperSet, $commands);
		$cli->run();
	}

	/**
	 * Creates a console application with the given helperset and optional commands.
	 *
	 * @param \Symfony\Component\Console\Helper\HelperSet $helperSet
	 * @param array $commands
	 * @return \Symfony\Component\Console\Application
	 */
	static public function createApplication(HelperSet $helperSet, $commands = [])
	{
		$cli = new Application('i-MSCP Frontend Command Line Interface', '1.3.0');
		$cli->setCatchExceptions(true);
		$cli->setHelperSet($helperSet);
		self::addCommands($cli);
		$cli->addCommands($commands);

		return $cli;
	}

	/**
	 * Add commands
	 *
	 * @param Application $cli
	 * @return void
	 */
	static public function addCommands(Application $cli)
	{
		$cli->addCommands([
			#
			# i-MSCP commands
			#
			new BuildLanguageIndexCommand(),
			new UpdateDatabaseCommand(),

			#
			# Doctrine commands
			#

			// DBAL Commands
			new RunSqlCommand(),
			new ImportCommand(),

			// ORM Commands
			new MetadataCommand(),
			new ResultCommand(),
			new QueryCommand(),
			new CreateCommand(),
			new UpdateCommand(),
			new DropCommand(),
			new EnsureProductionSettingsCommand(),
			new ConvertDoctrine1SchemaCommand(),
			new GenerateRepositoriesCommand(),
			new GenerateEntitiesCommand(),
			new GenerateProxiesCommand(),
			new ConvertMappingCommand(),
			new RunDqlCommand(),
			new ValidateSchemaCommand(),
			new InfoCommand(),
			new MappingDescribeCommand()
		]);
	}
}
