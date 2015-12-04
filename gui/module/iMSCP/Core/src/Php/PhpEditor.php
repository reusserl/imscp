<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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

namespace iMSCP\Core\Php;

use iMSCP\Core\Application;
use iMSCP\Core\Config\FileConfigHandler;

/**
 * Class PhpEditor
 * @package iMSCP\Core\Php
 */
class PhpEditor
{
    /**
     * iMSCP_PHPini instance.
     *
     * @var PhpEditor
     */
    static protected $instance;
    /**
     * Flag that is set to TRUE if an error occurs at {link setData()}.
     *
     * @var bool
     */
    public $flagValueError = false;
    /**
     * Flag that is sets to TRUE if the loaded data are customized
     *
     * @var bool
     */
    public $flagCustomIni;
    /**
     *  Flag that is sets to TRUE if an error occurs at setClPerm().
     *
     * @var bool
     */
    public $flagValueClError = false;
    /**
     * Associative array that contains php.ini data.
     *
     * @var array
     */
    protected $phpiniData = [];
    /**
     * Associative array that contains the reseller's permissions, including its max values for PHP directives.
     *
     * @var array
     */
    protected $phpiniRePerm = [];
    /**
     * Associative array that contains client permissions.
     *
     * @var array
     */
    protected $phpiniClPerm = [];
    /**
     * @var FileConfigHandler
     */
    protected $cfg;

    /**
     * Singleton object - Make new unavailable.
     */
    private function __construct()
    {
        $this->cfg = Application::getInstance()->getServiceManager()->get('config');

        // Populate $_phpiniData with default data.
        // Default data are those set by admin via the admin/settings.php page
        $this->loadDefaultData();

        // Populate $_phpiniRePerm with default reseller permissions, including
        // its max values for the PHP directives. Max values are those set by admin via the admin/settings.php page
        $this->loadReDefaultPerm();

        // Populate $_phpiniClPerm with default customer permissions
        $this->loadClDefaultPerm();
    }

    /**
     * Load default PHP directive values (like set at system wide).
     *
     * @return void
     * @TODO do not use system wide values as default values if reseller values are smaller
     */
    public function loadDefaultData()
    {
        $this->phpiniData['phpiniSystem'] = 'no';

        // Default permissions on PHP directives
        $this->phpiniData['phpiniAllowUrlFopen'] = $this->cfg->PHPINI_ALLOW_URL_FOPEN;
        $this->phpiniData['phpiniDisplayErrors'] = $this->cfg->PHPINI_DISPLAY_ERRORS;
        $this->phpiniData['phpiniErrorReporting'] = $this->cfg->PHPINI_ERROR_REPORTING;
        $this->phpiniData['phpiniDisableFunctions'] = $this->cfg->PHPINI_DISABLE_FUNCTIONS;

        // Default value for PHP directives
        $this->phpiniData['phpiniPostMaxSize'] = $this->cfg->PHPINI_POST_MAX_SIZE;
        $this->phpiniData['phpiniUploadMaxFileSize'] = $this->cfg->PHPINI_UPLOAD_MAX_FILESIZE;
        $this->phpiniData['phpiniMaxExecutionTime'] = $this->cfg->PHPINI_MAX_EXECUTION_TIME;
        $this->phpiniData['phpiniMaxInputTime'] = $this->cfg->PHPINI_MAX_INPUT_TIME;
        $this->phpiniData['phpiniMemoryLimit'] = $this->cfg->PHPINI_MEMORY_LIMIT;

        $this->flagCustomIni = false;
    }

    /**
     * Load default permissions and max values for reseller.
     *
     * @return void
     */
    public function loadReDefaultPerm()
    {
        // Default permissions on PHP directives
        $this->phpiniRePerm['phpiniSystem'] = 'no';
        $this->phpiniRePerm['phpiniAllowUrlFopen'] = 'no';
        $this->phpiniRePerm['phpiniDisplayErrors'] = 'no';
        $this->phpiniRePerm['phpiniDisableFunctions'] = 'no';

        // Default reseller max value for PHP directives (based on system wide values)
        $this->phpiniRePerm['phpiniPostMaxSize'] = $this->cfg->PHPINI_POST_MAX_SIZE;
        $this->phpiniRePerm['phpiniUploadMaxFileSize'] = $this->cfg->PHPINI_UPLOAD_MAX_FILESIZE;
        $this->phpiniRePerm['phpiniMaxExecutionTime'] = $this->cfg->PHPINI_MAX_EXECUTION_TIME;
        $this->phpiniRePerm['phpiniMaxInputTime'] = $this->cfg->PHPINI_MAX_INPUT_TIME;
        $this->phpiniRePerm['phpiniMemoryLimit'] = $this->cfg->PHPINI_MEMORY_LIMIT;
    }

    /**
     * Load default PHP editor permissions.
     *
     * @return void
     */
    public function loadClDefaultPerm()
    {
        $this->phpiniClPerm['phpiniSystem'] = 'no';
        $this->phpiniClPerm['phpiniAllowUrlFopen'] = 'no';
        $this->phpiniClPerm['phpiniDisplayErrors'] = 'no';
        $this->phpiniClPerm['phpiniDisableFunctions'] = 'no';
    }

    /**
     * Implements singleton design pattern.
     *
     * @static
     * @return PhpEditor
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Load custom PHP directive values for the given domain (customer).
     *
     * @param int $domainId Domain unique identifier
     * @return bool FALSE if data are not found, TRUE otherwise
     */
    public function loadCustomPHPini($domainId)
    {
        $query = "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
        $stmt = exec_query($query, (int)$domainId);

        if ($stmt->rowCount()) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->phpiniData['phpiniSystem'] = 'yes';
            $this->phpiniData['phpiniAllowUrlFopen'] = $row['allow_url_fopen'];
            $this->phpiniData['phpiniDisplayErrors'] = $row['display_errors'];
            $this->phpiniData['phpiniErrorReporting'] = $row['error_reporting'];
            $this->phpiniData['phpiniDisableFunctions'] = $row['disable_functions'];
            $this->phpiniData['phpiniPostMaxSize'] = $row['post_max_size'];
            $this->phpiniData['phpiniUploadMaxFileSize'] = $row['upload_max_filesize'];
            $this->phpiniData['phpiniMaxExecutionTime'] = $row['max_execution_time'];
            $this->phpiniData['phpiniMaxInputTime'] = $row['max_input_time'];
            $this->phpiniData['phpiniMemoryLimit'] = $row['memory_limit'];
            $this->flagCustomIni = true;
        }

        return false;
    }

    /**
     * Load permissions and max PHP directive values for the given reseller.
     *
     * @param int $resellerId Reseller unique identifier
     * @return bool FALSE if $resellerId doesn't exist, TRUE otherwise
     */
    public function loadRePerm($resellerId)
    {
        $resellerId = (int)$resellerId;

        $query = "
            SELECT
                `php_ini_system`, `php_ini_al_disable_functions`, `php_ini_al_allow_url_fopen`,
                `php_ini_al_display_errors`, `php_ini_max_post_max_size`, `php_ini_max_upload_max_filesize`,
                `php_ini_max_max_execution_time`, `php_ini_max_max_input_time`, `php_ini_max_memory_limit`
            FROM
                `reseller_props`
            WHERE
                `reseller_id` = ?
        ";
        $stmt = exec_query($query, $resellerId);


        if ($stmt->rowCount()) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row['php_ini_system'] == 'yes') {
                // Permissions on PHP directives
                $this->phpiniRePerm['phpiniSystem'] = 'yes';
                $this->phpiniRePerm['phpiniAllowUrlFopen'] = $row['php_ini_al_allow_url_fopen'];
                $this->phpiniRePerm['phpiniDisplayErrors'] = $row['php_ini_al_display_errors'];
                $this->phpiniRePerm['phpiniDisableFunctions'] = $row['php_ini_al_disable_functions'];

                // Max values for PHP directives
                $this->phpiniRePerm['phpiniPostMaxSize'] = $row['php_ini_max_post_max_size'];
                $this->phpiniRePerm['phpiniUploadMaxFileSize'] = $row['php_ini_max_upload_max_filesize'];
                $this->phpiniRePerm['phpiniMaxExecutionTime'] = $row['php_ini_max_max_execution_time'];
                $this->phpiniRePerm['phpiniMaxInputTime'] = $row['php_ini_max_max_input_time'];
                $this->phpiniRePerm['phpiniMemoryLimit'] = $row['php_ini_max_memory_limit'];

                return true;
            }
        }

        return false;
    }

    /**
     * Sets value for the given PHP directive.
     *
     * @see rawCheckData()
     * @param string $key PHP data key name
     * @param string $value PHP data value
     * @param bool $withCheck Tells whether or not the value must be checked
     * @return bool FALSE if $withCheck is set to TRUE and $value is not valid or if $keys is unknown, TRUE otherwise
     */
    public function setData($key, $value, $withCheck = true)
    {
        if (!$withCheck) {
            if ($key == 'phpiniErrorReporting') {
                $this->phpiniData[$key] = $this->errorReportingToInteger($value);
            } else {
                $this->phpiniData[$key] = $value;
            }

            return true;
        } elseif ($this->rawCheckData($key, $value)) {
            if ($key == 'phpiniErrorReporting') {
                $this->phpiniData[$key] = $this->errorReportingToInteger($value);
            } else {
                $this->phpiniData[$key] = $value;
            }

            return true;
        }

        $this->flagValueError = true;

        return false;
    }

    /**
     * Returns error reporting integer value
     *
     * @param string $value Litteral error reporting value such as 'E_ALL & ~E_NOTICE'
     * @return int error reporing integer value
     */
    public function errorReportingToInteger($value)
    {
        switch ($value) {
            case 'E_ALL & ~E_NOTICE':
                $int = E_ALL & ~E_NOTICE;
                break;
            case 'E_ALL | E_STRICT':
                $int = E_ALL | E_STRICT;
                break;
            case 'E_ALL & ~E_DEPRECATED':
                $int = E_ALL & ~E_DEPRECATED;
                break;
            default:
                $int = 0;
        }

        return $int;
    }

    /**
     * Checks value for the given PHP data.
     *
     * @param string $key PHP data key name
     * @param string $value PHP data value
     * @return bool TRUE if $key is known and $value is valid, FALSE otherwise
     */
    protected function rawCheckData($key, $value)
    {
        if ($key == 'phpiniSystem' && ($value == 'yes' || $value == 'no')) {
            return true;
        }

        if ($key == 'phpiniAllowUrlFopen' && ($value == 'on' || $value == 'off')) {
            return true;
        }

        if ($key == 'phpiniDisplayErrors' && ($value == 'on' || $value == 'off')) {
            return true;
        }

        if ($key == 'phpiniErrorReporting' && ($value == 'E_ALL & ~E_NOTICE' || $value == 'E_ALL | E_STRICT' ||
                $value == 'E_ALL & ~E_DEPRECATED' || $value == '0')
        ) {
            return true;
        }

        if ($key == 'phpiniDisableFunctions' && $this->checkDisableFunctionsSyntax($value)) {
            return true;
        }

        if ($key == 'phpiniPostMaxSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniUploadMaxFileSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniMaxExecutionTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniMaxInputTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniMemoryLimit' && $value > 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        return false;
    }

    /**
     * Checks value for the PHP disable_functions directive.
     *
     * Note: $disabledFunctions can be an array where each value is a function name, or a string where function names
     * are comma separated. An empty array or an empty string is also valid.
     *
     * @param array|string $disabledFunctions PHP function to be disabled
     * @return bool True if the $disabledFunctions contains only functions that can be disabled, FALSE otherwise
     */
    protected function checkDisableFunctionsSyntax($disabledFunctions)
    {
        $defaultDisabledFunctions = [
            'show_source', 'system', 'shell_exec', 'passthru', 'exec', 'shell', 'symlink', 'phpinfo', 'proc_open',
            'popen'
        ];

        if (!empty($disabledFunctions)) {
            if (is_string($disabledFunctions)) {
                $disabledFunctions = explode(',', $disabledFunctions);
            }

            foreach ($disabledFunctions as $function) {
                if (!in_array($function, $defaultDisabledFunctions)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sets value for the given customer permission.
     *
     * @param string $key Permission key name
     * @param string $value Permission value (yes|no)
     * @return bool FALSE if $value is not valid or if $keys is unknown, TRUE otherwise
     */
    public function setClPerm($key, $value)
    {
        if ($this->rawCheckClPermData($key, $value)) {
            $this->phpiniClPerm[$key] = $value;
            return true;
        }

        $this->flagValueClError = true;
        return false;
    }

    /**
     * Checks value for the given customer permission.
     *
     * @param string $key Permission key name
     * @param string $value Permission value
     * @return bool TRUE if $key is a known permission and $value is valid, FALSE otherwise
     */
    protected function rawCheckClPermData($key, $value)
    {
        if ($key == 'phpiniSystem' && ($value === 'yes' || $value === 'no')) {
            return true;
        }

        if ($key == 'phpiniAllowUrlFopen' && ($value === 'yes' || $value === 'no')) {
            return true;
        }

        if ($key == 'phpiniDisplayErrors' && ($value === 'yes' || $value === 'no')) {
            return true;
        }

        if ($key == 'phpiniDisableFunctions' && ($value === 'yes' || $value === 'no' || $value === 'exec')) {
            return true;
        }

        return false;
    }

    /**
     * Sets a PHP data.
     *
     * @param string $key PHP data key name
     * @param string $value PHP data value
     * @return bool FALSE if basic check or/and reseller permission check fails or if $key is unknown
     */
    public function setDataWithPermCheck($key, $value)
    {
        if ($this->rawCheckData($key, $value)) { // Value is not out of range
            // Either, the reseller has permissions on $key or $value is not greater than reseller max value for $key
            if ($this->checkRePerm($key) || $this->checkRePermMax($key, $value)) {
                $this->phpiniData[$key] = $value;
                return true;
            }
        }

        $this->flagValueError = true;
        return false;
    }

    /**
     * Checks if a reseller has permission on the given item.
     *
     * @param string $key Permission key name
     * @return bool TRUE if $key is a known item and reseller has permission on it.
     */
    public function checkRePerm($key)
    {
        if ($this->phpiniRePerm['phpiniSystem'] == 'yes') {
            if (
                $key == 'phpiniSystem' || in_array(
                    $key, array('phpiniAllowUrlFopen', 'phpiniDisplayErrors', 'phpiniDisableFunctions')
                ) && $this->phpiniRePerm[$key] == 'yes'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks value for the given customer PHP directive against the Max value allowed for reseller
     *
     * @param string $key Permission key name
     * @param string $value PHP directive value
     * @return bool TRUE if $value is valid max value, FALSE otherwise
     */
    public function checkRePermMax($key, $value)
    {
        if ($this->phpiniRePerm['phpiniSystem'] == 'yes') {
            if (
                in_array($key, [
                        'phpiniPostMaxSize', 'phpiniUploadMaxFileSize',
                        'phpiniMaxExecutionTime', 'phpiniMaxInputTime',
                        'phpiniMemoryLimit', '']
                ) && $value <= $this->phpiniRePerm[$key]
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets value for the given reseller permission.
     *
     * @param string $key Permission key name
     * @param string $value Permission value
     * @param bool $withCheck Tells whether or not the value must be checked
     * @return bool FALSE if $value is not valid or $key is unknown, TRUE otherwise.
     */
    public function setRePerm($key, $value, $withCheck = true)
    {
        if (!$withCheck) {
            $this->phpiniRePerm[$key] = $value;
            return true;
        } elseif ($this->rawCheckRePermData($key, $value)) {
            $this->phpiniRePerm[$key] = $value;
            return true;
        }

        $this->flagValueError = true;
        return false;
    }

    /**
     * Checks value for the given reseller PHP data.
     *
     * @param string $key PHP data key name
     * @param string $value PHP data value
     * @return bool TRUE if $key is known and $value is valid, FALSE otherwise
     */
    protected function rawCheckRePermData($key, $value)
    {
        if ($key == 'phpiniSystem' && ($value == 'yes' || $value == 'no')) {
            return true;
        }

        if ($key == 'phpiniAllowUrlFopen' && ($value === 'yes' || $value === 'no')) {
            return true;
        }

        if ($key == 'phpiniDisplayErrors' && ($value === 'yes' || $value === 'no')) {
            return true;
        }

        if ($key == 'phpiniDisableFunctions' && ($value === 'yes' || $value === 'no')) {
            return true;
        }

        // TODO review all min. values below

        if ($key == 'phpiniPostMaxSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniUploadMaxFileSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniMaxExecutionTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniMaxInputTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        if ($key == 'phpiniMemoryLimit' && $value > 0 && $value < 10000 && is_numeric($value)) {
            return true;
        }

        return false;
    }

    /**
     * Assemble disable_functions parameter from its parts.
     *
     * @param array $disabledFunctions
     * @return string
     */
    public function assembleDisableFunctions($disabledFunctions)
    {
        if (!empty($disabledFunctions)) {
            $disabledFunctions = implode(',', array_unique($disabledFunctions));
        } else {
            $disabledFunctions = '';
        }

        return $disabledFunctions;
    }

    /**
     * Saves custom PHP directives values into database.
     *
     * @param int $domainId Domain unique identifier
     * @return void
     */
    public function saveCustomPHPiniIntoDb($domainId)
    {
        if ($this->checkExistCustomPHPini($domainId)) {
            $query = "
                UPDATE
                    `php_ini`
                SET
                    `disable_functions` = ?, `allow_url_fopen` = ?, `display_errors` = ?,
                    `error_reporting` = ?, `post_max_size` = ?, `upload_max_filesize` = ?, `max_execution_time` = ?,
                    `max_input_time` = ?, `memory_limit` = ?
                WHERE
                    `domain_id` = ?
            ";
            exec_query($query, [
                $this->phpiniData['phpiniDisableFunctions'], $this->phpiniData['phpiniAllowUrlFopen'],
                $this->phpiniData['phpiniDisplayErrors'], $this->phpiniData['phpiniErrorReporting'],
                $this->phpiniData['phpiniPostMaxSize'], $this->phpiniData['phpiniUploadMaxFileSize'],
                $this->phpiniData['phpiniMaxExecutionTime'], $this->phpiniData['phpiniMaxInputTime'],
                $this->phpiniData['phpiniMemoryLimit'], $domainId
            ]);
        } else {
            $query = "
                INSERT INTO `php_ini` (
                    `disable_functions`, `allow_url_fopen`, `display_errors`, `error_reporting`, `post_max_size`,
                    `upload_max_filesize`, `max_execution_time`, `max_input_time`, `memory_limit`, `domain_id`
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ";
            exec_query($query, [
                $this->phpiniData['phpiniDisableFunctions'], $this->phpiniData['phpiniAllowUrlFopen'],
                $this->phpiniData['phpiniDisplayErrors'], $this->phpiniData['phpiniErrorReporting'],
                $this->phpiniData['phpiniPostMaxSize'], $this->phpiniData['phpiniUploadMaxFileSize'],
                $this->phpiniData['phpiniMaxExecutionTime'], $this->phpiniData['phpiniMaxInputTime'],
                $this->phpiniData['phpiniMemoryLimit'], $domainId
            ]);
        }
    }

    /**
     * Checks if custom PHP directives exists for the given domain (customer).
     *
     * @param int $domainId Domain unique identifier
     * @return bool TRUE custom PHP directive are found for $domainId, FALSE otherwise
     */
    public function checkExistCustomPHPini($domainId)
    {
        $query = 'SELECT COUNT(`domain_id`) `cnt` FROM `php_ini` WHERE `domain_id` = ?';
        $stmt = exec_query($query, (int)$domainId);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row['cnt'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Update domain table status and send request to the daemon.
     *
     * @param int $domainId Domain unique identifier
     * @return void
     */
    public function sendToEngine($domainId)
    {
        exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', ['tochange', $domainId]);
        exec_query('UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ?', ['tochange', $domainId]);
        send_request();
    }

    /**
     * Deletes custom PHP directive values for the given domain (customer).
     *
     * @param int $domainId Domain unique identifier
     * @return void
     */
    public function delCustomPHPiniFromDb($domainId)
    {
        if ($this->checkExistCustomPHPini($domainId)) {
            $query = "DELETE FROM `php_ini` WHERE `domain_id` = ?";
            exec_query($query, $domainId);
        }
    }

    /**
     * Saves PHP editor permissions for the given (customer).
     *
     * @param int $domainId Domain unique identifier
     * @return void
     */
    public function saveClPermIntoDb($domainId)
    {
        $query = "
            UPDATE
                `domain`
            SET
                `phpini_perm_system` = ?, `phpini_perm_allow_url_fopen` = ?, `phpini_perm_display_errors` = ?,
                `phpini_perm_disable_functions` = ?
            WHERE
                `domain_id` = ?
        ";
        exec_query($query, [
            $this->phpiniClPerm['phpiniSystem'], $this->phpiniClPerm['phpiniAllowUrlFopen'],
            $this->phpiniClPerm['phpiniDisplayErrors'], $this->phpiniClPerm['phpiniDisableFunctions'],
            $domainId
        ]);
    }

    /**
     * Returns the PHP data as currently set.
     *
     * @return array
     */
    public function getData()
    {
        return $this->phpiniData;
    }

    /**
     * Returns reseller permissions like currently set in this object.
     *
     * @return array
     */
    public function getRePerm()
    {
        return $this->phpiniRePerm;
    }

    /**
     * Returns default value for the giver reseller permission.
     *
     * @param string $key Permissions key name
     * @return string Permissions value
     */
    public function getReDefaultPermVal($key)
    {
        return min($this->getRePermVal($key), $this->getDataVal($key));
    }

    /**
     * Returns value for the given reseller permission.
     *
     * @param string $key Permission key name
     * @return string Permissions value
     */
    public function getRePermVal($key)
    {
        return $this->phpiniRePerm[$key];
    }

    /**
     * Returns value for the given PHP data.
     *
     * @param string $key PHP data key name
     * @return string PHP data value
     */
    public function getDataVal($key)
    {
        return $this->phpiniData[$key];
    }

    /**
     * Returns customer permissions like currently set in this object.
     *
     * @return array
     */
    public function getClPerm()
    {
        return $this->phpiniClPerm;
    }

    /**
     * Returns value for the given customer permission.
     *
     * @param string $key Permissions key name
     * @return string Permission value
     */
    public function getClPermVal($key)
    {
        return $this->phpiniClPerm[$key];
    }

    /**
     * Returns default value for the given PHP directive.
     *
     * @param string $key PHP data key name
     * @returns string PHP data value
     */
    public function getDataDefaultVal($key)
    {
        $phpiniDatatmp['phpiniSystem'] = 'no';
        $phpiniDatatmp['phpiniAllowUrlFopen'] = $this->cfg->PHPINI_ALLOW_URL_FOPEN;
        $phpiniDatatmp['phpiniDisplayErrors'] = $this->cfg->PHPINI_DISPLAY_ERRORS;
        $phpiniDatatmp['phpiniErrorReporting'] = $this->cfg->PHPINI_ERROR_REPORTING;
        $phpiniDatatmp['phpiniDisableFunctions'] = $this->cfg->PHPINI_DISABLE_FUNCTIONS;
        $phpiniDatatmp['phpiniPostMaxSize'] = $this->cfg->PHPINI_POST_MAX_SIZE;
        $phpiniDatatmp['phpiniUploadMaxFileSize'] = $this->cfg->PHPINI_UPLOAD_MAX_FILESIZE;
        $phpiniDatatmp['phpiniMaxExecutionTime'] = $this->cfg->PHPINI_MAX_EXECUTION_TIME;
        $phpiniDatatmp['phpiniMaxInputTime'] = $this->cfg->PHPINI_MAX_INPUT_TIME;
        $phpiniDatatmp['phpiniMemoryLimit'] = $this->cfg->PHPINI_MEMORY_LIMIT;
        return $phpiniDatatmp[$key];
    }

    /**
     * Load PHP editor permissions for the given domain (customer).
     *
     * @param int $domainId Domain unique identifier
     * @return bool FALSE if there no data for $domainId
     */
    public function loadClPerm($domainId)
    {
        $query = "
            SELECT
                `phpini_perm_system`, `phpini_perm_allow_url_fopen`, `phpini_perm_display_errors`,
                `phpini_perm_disable_functions`
            FROM
                `domain`
            WHERE
                `domain_id` = ?
        ";
        $stmt = exec_query($query, (int)$domainId);

        if ($stmt->rowCount()) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->phpiniClPerm['phpiniSystem'] = $row['phpini_perm_system'];
            $this->phpiniClPerm['phpiniAllowUrlFopen'] = $row['phpini_perm_allow_url_fopen'];
            $this->phpiniClPerm['phpiniDisplayErrors'] = $row['phpini_perm_display_errors'];
            $this->phpiniClPerm['phpiniDisableFunctions'] = $row['phpini_perm_disable_functions'];

            return true;
        }

        return false;
    }

    /**
     * Returns domain unique identifier for the given customer identifier.
     *
     * @param int $customerId Customer unique identifier
     * @return mixed
     */
    public function getDomId($customerId)
    {
        $query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";
        $stmt = exec_query($query, $customerId);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row['domain_id'];
    }

    /**
     * Tells whether or not the status is ok for the given domain.
     *
     * @param int $domainId Domain unique identifier
     * @return bool TRUE if domain status is 'ok', FALSE otherwise
     */
    public function getDomStatus($domainId)
    {
        $query = "SELECT `domain_status` FROM `domain` WHERE `domain_id` = ?";
        $stmt = exec_query($query, $domainId);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row['domain_status'] == 'ok') {
            return true;
        }

        return false;
    }

    /**
     * Returns error reporting litteral value
     *
     * @param int $value integer error reporting value
     * @return int error reporing litteral value
     */
    public function errorReportingToLitteral($value)
    {
        switch ($value) {
            case '30711':
            case '32759':
                $litteral = 'E_ALL & ~E_NOTICE';
                break;
            case '32767':
                $litteral = 'E_ALL | E_STRICT';
                break;
            case '22527':
            case '24575':
                $litteral = 'E_ALL & ~E_DEPRECATED';
                break;
            default:
                $litteral = 0;
        }

        return $litteral;
    }

    /**
     * Singleton obect - Make clone unavailable.
     *
     * @return void
     */
    private function __clone()
    {

    }
}
