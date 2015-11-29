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

namespace iMSCP\Update\Command;

use iMSCP_Update_Database as DatabaseUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdatePackageIndexCommand
 * @package iMSCP\ApsStandard\Command
 */
class UpdateDatabaseCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('imscp:update:database')
			->setDescription('Update i-MSCP Database')
			->setHelp(<<<EOT
The <info>imscp:update-database</info> command update i-MSCP database

  <info>imscp:aps-standard:update-package-index</info>

EOT
			);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Update database schema (for tables which are not yet managed through ORM)
		DatabaseUpdater::getInstance()->applyUpdates();

		// Update database schema (for tables which are managed through ORM)
		$this->getApplication()->find('orm:schema-tool:update')->run(
			new ArrayInput(['command' => 'orm:schema-tool:update', '-f' => true]), $output
		);

		$output->writeln('Database schema updated successfully!');
	}
}
