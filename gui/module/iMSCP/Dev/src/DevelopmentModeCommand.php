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

namespace iMSCP\Dev;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DevelopmentModeCommand
 * @package iMSCP\Dev
 */
class DevelopmentModeCommand extends Command
{
    const CONFIG_CACHE_BASE = 'module-config-cache';

    /**
     * @var string Configuration cache directory, if any
     */
    private $configCacheDir;

    /**
     * @var string Configuration cache key, if any
     */
    private $configCacheKey;

    /**
     * Constructor
     *
     * @param null|string $configCacheDir
     * @param $configCacheKey
     */
    public function __construct($configCacheDir, $configCacheKey)
    {
        parent::__construct('imscp:development:mode');

        $this->configCacheDir = $configCacheDir;
        $this->configCacheKey = $configCacheKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Enable or disable development mode (FrontEnd)')
            ->addArgument('action', InputArgument::REQUIRED, 'Enable/Disable development mode')
            ->addArgument('action', InputArgument::REQUIRED, 'Enable/Disable development mode')
            ->setHelp(<<<EOT
The <info>imscp:development:mode</info> command enable or disable development mode:

  <info>imscp:development:mode enable</info> Enable development mode (do not use in production)
  <info>imscp:development:mode disable</info> Disable development mode

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('name');

        if (!in_array($action, ['enable', 'disable'])) {
            throw new \InvalidArgumentException(sprintf(
                "Unknown action '<info>%s</info>'.", $action
            ));
        }

        if ($action == 'enable') {
            $message = $this->enableDevelopmentMode();
        } else {
            $message = $this->disableDevelopmentMode();
        }

        $output->writeln($message);
    }

    /**
     * Enable development mode
     */
    private function enableDevelopmentMode()
    {
        if (file_exists('config/development.config.php')) {
            return 'Already in development mode!';
        }

        if (!file_exists('config/development.config.php.dist')) {
            return 'MISSING `config/development.config.php.dist`. Could not switch to development mode!';
        }

        copy('config/development.config.php.dist', 'config/development.config.php');

        if (file_exists('config/autoload/development.local.php.dist')) {
            // optional application config override
            copy('config/autoload/development.local.php.dist', 'config/autoload/development.local.php');
        }

        $this->removeConfigCacheFile($this->getConfigCacheFile());
        $this->disableOPCache();

        return 'You are now in development mode.';
    }

    /**
     * Disable development mode
     */
    private function disableDevelopmentMode()
    {
        if (!file_exists('config/development.config.php')) {
            return 'Development mode was already disabled.';
        }

        if (file_exists('config/autoload/development.local.php')) {
            // optional application config override
            unlink('config/autoload/development.local.php');
        }

        unlink('config/development.config.php');
        $this->removeConfigCacheFile($this->getConfigCacheFile());
        $this->enableOPCache();

        return 'Development mode is now disabled.';
    }

    /**
     * Removes the application configuration cache file, if present
     *
     * @param $configCacheFile
     */
    private function removeConfigCacheFile($configCacheFile)
    {
        if ($configCacheFile && file_exists($configCacheFile)) {
            unlink($configCacheFile);
        }
    }

    /**
     * Retrieve the config cache file, if any
     *
     * @return false|string
     */
    private function getConfigCacheFile()
    {
        if (empty($this->configCacheDir)) {
            return false;
        }

        $path = sprintf('%s/%s.', $this->configCacheDir, self::CONFIG_CACHE_BASE);

        if (!empty($this->configCacheKey)) {
            $path .= $this->configCacheKey . '.';
        }

        return $path . 'php';
    }

    /**
     * Disable opcode cache if any
     *
     * @return void
     */
    private function disableOPCache()
    {
        exec('php5dismod apc opcache xcache 2>/dev/null', $out, $ret);
        exec('service imscp_panel restart', $out, $ret);
    }

    /**
     * Enable opcode cache if any
     *
     * @return void
     */
    public function enableOPCache()
    {
        exec('php5enmod apc opcache 2>/dev/null', $out, $ret);
        exec('service imscp_panel restart', $out, $ret);
    }
}
