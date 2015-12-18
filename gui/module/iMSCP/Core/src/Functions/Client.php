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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Checks if the given user is the owner of the given SQL user
 *
 * @param int $userId User identifier
 * @param int $sqlUserId SQL user identifier
 * @return bool TRUE if the given user is the owner of the given SQL user
 */
function checkSqlUserOwner($userId, $sqlUserId)
{
    $userId = (int)$userId;
    return ($userId === (int)who_owns_this($sqlUserId, 'sqlu_id'));
}

/**
 * Tells whether or not the authenticated customer can access to the given feature(s)
 *
 * @param array|string $featureNames Feature name(s) (insensitive case)
 * @param bool $forceReload If true force data to be reloaded
 * @return bool TRUE if $featureName is available for customer, FALSE otherwise
 */
function customerHasFeature($featureNames, $forceReload = false)
{
    static $availableFeatures = null;
    static $debug = false;

    if (null === $availableFeatures || $forceReload) {
        $cfg = \iMSCP\Core\Application::getInstance()->getConfig();
        $debug = (bool)$cfg['DEBUG'];
        $dmnProps = get_domain_default_props($_SESSION['user_id']);

        $availableFeatures = [
            /*'domain' => ($dmnProps['domain_alias_limit'] != '-1'
                || $dmnProps['domain_subd_limit'] != '-1'
                || $dmnProps['domain_dns'] == 'yes'
                || $dmnProps['phpini_perm_system'] == 'yes'
                || $cfg['ENABLE_SSL']) ? true : false,
            */
            'external_mail' => ($dmnProps['domain_external_mail'] == 'yes') ? true : false,
            'php' => ($dmnProps['domain_php'] == 'yes') ? true : false,
            'php_editor' => ($dmnProps['phpini_perm_system'] == 'yes' &&
                ($dmnProps['phpini_perm_allow_url_fopen'] == 'yes'
                    || $dmnProps['phpini_perm_display_errors'] == 'yes'
                    || in_array($dmnProps['phpini_perm_disable_functions'], ['yes', 'exec']))) ? true : false,
            'cgi' => ($dmnProps['domain_cgi'] == 'yes') ? true : false,
            'ftp' => ($dmnProps['domain_ftpacc_limit'] != '-1') ? true : false,
            'sql' => ($dmnProps['domain_sqld_limit'] != '-1') ? true : false,
            'mail' => ($dmnProps['domain_mailacc_limit'] != '-1') ? true : false,
            'subdomains' => ($dmnProps['domain_subd_limit'] != '-1') ? true : false,
            'domain_aliases' => ($dmnProps['domain_alias_limit'] != '-1') ? true : false,
            'custom_dns_records' =>
                ($dmnProps['domain_dns'] != 'no' && $cfg['NAMED_SERVER'] != 'external_server') ? true : false,
            'aps_standard' => ($dmnProps['aps_standard'] == 'yes') ? true : false,
            'webstats' => ($cfg['WEBSTATS_PACKAGES'] != 'No') ? true : false,
            'backup' => ($cfg['BACKUP_DOMAINS'] != 'no' && $dmnProps['allowbackup'] != '') ? true : false,
            'protected_areas' => true,
            'custom_error_pages' => true,
            'ssl' => ($cfg['ENABLE_SSL']) ? true : false
        ];

        if (($cfg['IMSCP_SUPPORT_SYSTEM'])) {
            $stmt = exec_query(
                'SELECT support_system FROM reseller_props WHERE reseller_id = ?', $_SESSION['user_created_by']
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $availableFeatures['support'] = ($row['support_system'] == 'yes') ? true : false;
        } else {
            $availableFeatures['support'] = false;
        }
    }

    $canAccess = true;
    foreach ((array)$featureNames as $featureName) {
        $featureName = strtolower($featureName);

        if ($debug && !array_key_exists($featureName, $availableFeatures)) {
            throw new InvalidArgumentException(sprintf(
                "Feature %s is not known by the customerHasFeature() function.", $featureName
            ));
        }

        if (!$availableFeatures[$featureName]) {
            $canAccess = false;
            break;
        }
    }

    return $canAccess;
}

/**
 * Tells whether or not the current customer can access the mail or external mail feature
 * @return bool
 */
function customerHasMailOrExtMailFeatures()
{
    return (customerHasFeature('mail') || customerHasFeature('external_mail'));
}

/**
 * Checks if the given user is the owner of the given domain name
 *
 * @param int $customerId User identifier
 * @param string $domainName Domain name
 * @return bool TRUE if the given user is the owner of the given SQL user
 */
function checkDomainNameOwner($customerId, $domainName)
{
    $domainName = encode_idna($domainName);

    // Check for domain
    $stmt = exec_query("SELECT COUNT(*) AS cnt FROM domain WHERE domain_admin_id = ? AND domain_name = ?", [
        $customerId, $domainName
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        return true;
    }

    // Check for domain aliases
    $stmt = exec_query(
        "
            SELECT
                COUNT(*) AS cnt
            FROM
                domain AS t1
            INNER JOIN
                domain_aliasses AS t2 ON(t2.domain_id = t1.domain_id)
            WHERE
                t1.domain_admin_id = ?
            AND
                t2.alias_name = ?
        ",
        [$customerId, $domainName]
    );

    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        return true;
    }

    // Check for subdomains
    $stmt = exec_query(
        "
            SELECT
                 COUNT(*) AS cnt
            FROM
                domain AS t1
            INNER JOIN
                subdomain AS t2 ON (t2.domain_id = t1.domain_id)
            WHERE
                t1.domain_admin_id = ?
            AND
                CONCAT(t2.subdomain_name, '.', t1.domain_name) = ?
        ",
        [$customerId, $domainName]
    );

    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        return true;
    }

    // Check for subdomain aliases
    $stmt = exec_query(
        "
            SELECT
                COUNT(*) AS cnt
            FROM
                domain AS t1
            INNER JOIN
                domain_aliasses AS t2 ON(t2.domain_id = t1.domain_id)
            INNER JOIN
                 subdomain_alias AS t3 ON(t3.alias_id = t2.alias_id)
            WHERE
                t1.domain_admin_id = ?
            AND
                CONCAT(t3.subdomain_alias_name, '.', t2.alias_name) = ?
        ",
        [$customerId, $domainName]
    );

    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        return true;
    }

    return false;
}

/**
 * Delete all autoreplies log for which not mail address is found in the mail_users database table
 *
 * @return void
 */
function deleteAutorepliesLogEntries()
{
    exec_query('DELETE FROM autoreplies_log WHERE `from` NOT IN (SELECT mail_addr FROM mail_users)');
}
