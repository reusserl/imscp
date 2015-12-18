<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

namespace iMSCP\Core;

/**
 * Class NetworkCard
 * @package iMSCP\Core
 */
class NetworkCard
{
    /**
     * @var array Interface info
     */
    protected $interfacesInfo = [];

    /**
     * @var array Interfaces
     */
    protected $interfaces = [];

    /**
     * @var array Offline interfas
     */
    protected $offlineInterfaces = [];

    /**
     * @var array Virtual interfaces
     */
    protected $virtualInterfaces = [];

    /**
     * @var array Available interfaces
     */
    protected $availableInterfaces = [];

    /**
     * @var array Errors
     */
    protected $errors = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getInterface();
        $this->populateInterfaces();
    }

    /**
     * Get device information
     *
     * @return array
     */
    public function network()
    {
        $file = $this->read('/proc/net/dev');
        preg_match_all('/(.+):.+/', $file, $dev_name);
        return $dev_name[1];
    }

    /**
     * Read the given file
     *
     * @param string $filename
     * @return string
     */
    public function read($filename)
    {
        if (($result = @file_get_contents($filename)) === false) {
            $this->errors .= sprintf(tr("File %s doesn't exist or cannot be reached!"), $filename);
            return '';
        }

        return $result;
    }

    /**
     * Get list of available interface
     *
     * @return array
     */
    public function getAvailableInterface()
    {
        return $this->availableInterfaces;
    }

    /**
     * Get errors
     *
     * @return string
     */
    public function getErrors()
    {
        return nl2br($this->errors);
    }

    /**
     * Get netcard associated with the given IP address
     *
     * @param string $ip
     * @return null|string
     */
    public function ip2NetworkCard($ip)
    {
        $key = array_search($ip, $this->interfacesInfo[2]);

        if ($key === false) {
            $this->errors .= sprintf(tr("This IP (%s) is not assigned to any network card!"), $ip);
            return null;
        }

        return $this->interfacesInfo[1][$key];
    }

    /**
     * Load interface info
     *
     * @return void
     */
    protected function getInterface()
    {
        foreach ($this->network() as $key => $value) {
            $this->interfaces[] = trim($value);
        }
    }

    /**
     * Extract interface info
     *
     * @return void
     */
    protected function populateInterfaces()
    {
        $err = '';
        $message = $this->executeExternal('ifconfig -a', $err);

        if (!$message) {
            $this->errors .= tr('Error while trying to obtain list of network cards.') . $err;
            return;
        }

        preg_match_all("/(?isU)([^ ]{1,}) {1,}.+(?:(?:\n\n)|$)/", $message, $this->interfacesInfo);

        foreach ($this->interfacesInfo[0] as $a) {
            if (preg_match("/inet addr\\:([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})/", $a, $b)) {
                $this->interfacesInfo[2][] = trim($b[1]);
            } else {
                $this->interfacesInfo[2][] = '';
            }
        }

        $this->offlineInterfaces = array_diff($this->interfaces, $this->interfacesInfo[1]);
        $this->virtualInterfaces = array_diff($this->interfacesInfo[1], $this->interfaces);
        $this->availableInterfaces = array_diff(
            $this->interfaces, $this->offlineInterfaces, $this->virtualInterfaces, ['lo']
        );
    }

    /**
     * Execute external command
     *
     * @param string $strProgram
     * @param string &$strError
     * @return bool|string
     */
    protected function executeExternal($strProgram, &$strError)
    {
        $strBuffer = '';
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $pipes = [];
        $process = proc_open($strProgram, $descriptorspec, $pipes);

        if (is_resource($process)) {
            while (!feof($pipes[1])) {
                $strBuffer .= fgets($pipes[1], 1024);
            }
            fclose($pipes[1]);

            while (!feof($pipes[2])) {
                $strError .= fgets($pipes[2], 1024);
            }
            fclose($pipes[2]);
        }

        $return_value = proc_close($process);
        $strError = trim($strError);
        $strBuffer = trim($strBuffer);

        if (!empty($strError) || $return_value != 0) {
            $strError .= "\nReturn value: " . $return_value;
            return false;
        }

        return $strBuffer;
    }
}
