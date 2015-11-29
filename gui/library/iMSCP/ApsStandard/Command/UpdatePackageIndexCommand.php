<?php

namespace iMSCP\ApsStandard\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdatePackageIndexCommand
 * @package iMSCP\ApsStandard\Command
 */
class UpdatePackageIndexCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('imscp:aps:update:package:index')
			->setDescription('Update APS standard package index')
			->setHelp(<<<EOT
The <info>imscp:aps-standard:update-package-index</info> command update APS standard package index:

  <info>imscp:aps-standard:update-package-index</info>

EOT
			);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->getHelper('sm')->get('ApsSpiderService')->exploreCatalog();
		$output->writeln('APS standard package index updated successfully!');
	}
}
