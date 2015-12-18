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

return [
    // isp logos path
    'ISP_LOGO_PATH' => '/ispLogos',

    'HTML_CHECKED' => ' checked="checked"',
    'HTML_DISABLED' => ' disabled="disabled"',
    'HTML_READONLY' => ' readonly="readonly"',
    'HTML_SELECTED' => ' selected="selected"',

    // User initial lang
    'USER_INITIAL_LANG' => 'auto',

    // Session timeout
    'SESSION_TIMEOUT' => 30,

    // SQL related settings
    'MAX_SQL_DATABASE_LENGTH' => 64,
    'MAX_SQL_USER_LENGTH' => 16,
    'MAX_SQL_PASS_LENGTH' => 32,

    // Captcha image width
    'LOSTPASSWORD_CAPTCHA_WIDTH' => 276,

    // Captcha image high
    'LOSTPASSWORD_CAPTCHA_HEIGHT' => 30,

    // Captcha background color
    'LOSTPASSWORD_CAPTCHA_BGCOLOR' => [176, 222, 245],

    // Captcha text color
    'LOSTPASSWORD_CAPTCHA_TEXTCOLOR' => [1, 53, 920],

    // Captcha ttf fontfiles (have to be under compatible open source license)
    'LOSTPASSWORD_CAPTCHA_FONTS' => [
        'FreeMono.ttf',
        'FreeMonoBold.ttf',
        'FreeMonoBoldOblique.ttf',
        'FreeMonoOblique.ttf',
        'FreeSans.ttf',
        'FreeSansBold.ttf',
        'FreeSansBoldOblique.ttf',
        'FreeSansOblique.ttf',
        'FreeSerif.ttf',
        'FreeSerifBold.ttf',
        'FreeSerifBoldItalic.ttf',
        'FreeSerifItalic.ttf'
    ],

    /**
     * The following settings can be overridden via the control panel - (admin/settings.php)
     * The value below are those used by default
     */

    // Domain rows pagination
    'DOMAIN_ROWS_PER_PAGE' => 10,

    // admin    : hosting plans are available only on admin level, the reseller cannot make custom changes
    // reseller : hosting plans are available only on reseller level
    'HOSTING_PLANS_LEVEL' => 10,

    // Enable or disable support system globally
    'IMSCP_SUPPORT_SYSTEM' => 1,

    // Enable or disable lost password support
    'LOSTPASSWORD' => 1,

    // Unique key timeout (in minutes).
    // These are the unique keys which are sent to users for password retrieval.
    'LOSTPASSWORD_TIMEOUT' => 30,

    // Enable or disable bruteforce detection plugin
    'BRUTEFORCE' => 1,

    // Time blocking (in minutes)
    // This is the time period for which the user will be blocked on too many
    // authentication attemps.
    'BRUTEFORCE_BLOCK_TIME' => 30,

    // Max authentication attemps
    // This is the max number of authentication attemps before the user is
    // blocked
    'BRUTEFORCE_MAX_LOGIN' => 3,

    // Max login attempts before forced to wait
    // This is the max number of authentication attemps before the user must
    // wait for the next attemps.
    'BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT' => 2,

    // Max captcha failed attempts before block
    'BRUTEFORCE_MAX_CAPTCHA' => 5,

    // Enable or disable time between logins
    'BRUTEFORCE_BETWEEN' => 1,

    // Time between logins in seconds
    'BRUTEFORCE_BETWEEN_TIME' => 30,

    // Enable or disable maintenance mode
    // 1: Maintenance mode enabled
    // 0: Maintenance mode disabled
    'MAINTENANCEMODE' => 0,

    // Minimum character length for passwords
    'PASSWD_CHARS' => 6,

    // Enable or disable strong passwords
    // 1: Strong password not allowed
    // 0: Strong password allowed
    'PASSWD_STRONG' => 1,

    /**
     * Logging Mailer default level (messages sent to DEFAULT_ADMIN_ADDRESS)
     *
     * E_USER_NOTICE: common operations (normal work flow).
     * E_USER_WARNING: Operations that may be related to a problem
     * E_USER_ERROR: Errors for which the admin should pay attention
     *
     * Note: PHP's E_USER_* constants are used for simplicity.
     */
    'LOG_LEVEL' => E_USER_WARNING,

    // Creation of default abuse, ftp, hostmaster, postmaster and webmaster email addresses
    // These are email addresses described by RFC 2142
    //
    // abuse:      Customer Relations - See RFC 2142 - (forwarded to reseller email address)
    // ftp         FTP service - RFC959 - (forwarded to reseller email address)
    // hostmaster: DNS service - See RFC1033 to RFC1035 - (forwarded to reseller email address)
    // postmaster: SMTP service - See RFC821 and RFC8822 - (forwarded to reseller email address)
    // webmaster:  HTTP service - RFC 2068 - (forwarded to customer email address)
    'CREATE_DEFAULT_EMAIL_ADDRESSES' => 1,

    // Count default email accounts (abuse, ftp, hostmaster, postmaster and webmaster) in user limit
    // 1: default email accounts are counted
    // 0: default email accounts are NOT counted
    'COUNT_DEFAULT_EMAIL_ADDRESSES' => 0,

    // Use hard mail suspension when suspending a domain:
    // 1: email accounts are hard suspended (completely unreachable)
    // 0: email accounts are soft suspended (passwords are modified so user can't access the accounts)
    'HARD_MAIL_SUSPENSION' => 1,

    // Prevent external login (i.e. check for valid local referer) separated in admin, reseller and client.
    // This option allows to use external login scripts
    //
    // 1: prevent external login, check for referer, more secure
    // 0: allow external login, do not check for referer, less security (risky)
    'PREVENT_EXTERNAL_LOGIN_ADMIN' => 1,
    'PREVENT_EXTERNAL_LOGIN_RESELLER' => 1,
    'PREVENT_EXTERNAL_LOGIN_CLIENT' => 1,

    // Automatic search for new version
    'CHECK_FOR_UPDATES' => false,

    // SSL support
    'ENABLE_SSL' => true,

    // Server traffic settings
    'SERVER_TRAFFIC_LIMIT' => 0,
    'SERVER_TRAFFIC_WARN' => 0,

    // Paths appended to the default PHP open_basedir directive of customers
    // This options is used on new domain creation only.
    'PHPINI_OPEN_BASEDIR' => '',

    //
    // DoctrineIntegration module configuration
    //

    'doctrine_integration' => [
        'connection' => [
            'imscp' => [
                'configuration' => 'imscp',
                'eventmanager' => 'imscp',
                // Map enum type to varchar type
                'doctrine_type_mappings' => [
                    'enum' => 'string'
                ],
            ]
        ],

        'configuration' => [
            'imscp' => [
                'metadata_cache' => 'array',
                'query_cache' => 'array',
                'result_cache' => 'array',
                'hydration_cache' => 'array',
                'driver' => 'imscp',
                'generate_proxies' => true,
                'proxy_dir' => './data/DoctrineIntegration/Proxy',
                'proxy_namespace' => 'iMSCP\DoctrineIntegration\Proxy',
                'entity_namespaces' => [
                    'Core' => 'iMSCP\Core\Entity'
                ],
                // Ignore tables which are not managed by ORM yet
                'filter_schema_asset_names' => [
                    'auth_bruteforce', 'autoreplies_log', 'config', 'custom_menus', 'domain', 'domain_aliasses',
                    'domain_dns', 'domain_traffic', 'email_tpls', 'error_pages', 'ftp_group', 'ftp_users',
                    'hosting_plans', 'htaccess', 'htaccess_groups', 'htaccess_users', 'httpd_vlogger', 'log', 'login',
                    'mail_users', 'php_ini', 'plugin', 'quotalimits', 'quotatallies', 'reseller_props', 'server_ips',
                    'server_traffic', 'sql_database', 'sql_user', 'ssl_certs', 'subdomain', 'subdomain_alias', 'tickets',
                    'user_gui_props'
                ]
            ]
        ],

        'driver' => [
            'imscp' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [
                    './module/iMSCP/Core/src/Entity'
                ]
            ],

            // Should we use driver chain?
            // Right now, it is assumed that all modules/plugins will
            // simply add entity paths to the imscp_annotation_driver driver
            // Doing this avoid useless loops through all namespaces...
            'imscp_annotation_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [
                    './module/iMSCP/Core/src/Entity'
                ]
            ],
            /*
            'imscp' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\DriverChain',
                // Add the driver in the chain
                'drivers' => [
                    'iMSCP\Core\Entity' => 'imscp_annotation_driver'
                ]
            ],
            */
        ],

        // Entity Manager instantiation settings
        'entitymanager' => [
            // Configuration for the `doctrine_integration.entitymanager.default` service
            'imscp' => [
                // Connection instance to use. The retrieved service name will
                // be `doctrine_integration.connection.$thisSetting`
                'connection' => 'imscp',

                // Configuration instance to use. The retrieved service name
                // will be `doctrine_integration.configuration.$thisSetting`
                'configuration' => 'imscp',

                // Entity resolver to use. The retrieved service name
                // will be `doctrine_integration.entity_resolver.$thisSetting`
                'entity_resolver' => 'imscp'
            ]
        ],

        // entity resolver configuration, allows mapping associations to interfaces
        'entity_resolver' => [
            // Configuration for the `doctrine.entity_resolver.default` service
            'imscp' => []
        ],

        'eventmanager' => [
            // Configuration for the `doctrine_integration.eventmanager.default` service
            'imscp' => []
        ],

        // Authentication service configuration
        'authentication' => [
            // Configuration for the `doctrine_integration.authentication.default`
            // authentication service
            'imscp' => [],
        ],
        'authenticationadapter' => [
            'imscp' => true
        ],
        'authenticationstorage' => [
            'imscp' => true
        ],
        'authenticationservice' => [
            'imscp' => true
        ],

        'manager_registry' => [
            'imscp' => [
                'default_connection' => 'imscp',
                'default_manager' => 'imscp'
            ]
        ]
    ],

    //
    // Session configuration
    //

    'session_config' => [
        'name' => 'iMSCP', // Session name
        // Session cookie settings
        'use_cookies' => true,
        'cookie_path' => '/',
        'cookie_lifetime' => 604800,
        'cookie_httponly' => true,
        'cookie_secure' => false,
        //'cache_expire' => 0,
        //'remember_me_seconds' => 604800, // Not implemented yet
        // PHP session related settings
        'gc_divisor' => 100,
        'gc_maxlifetime' => 1440,
        'gc_probability' => 100,
        'save_path' => './data/sessions', // Path where session files are stored
        'php_save_handler' => 'files', // Only for reference (it is the default value)
        'use_trans_sid' => false, // Should be false (security reason)
        'config_class' => 'Zend\Session\Config\SessionConfig', // Only for reference (it is the default value)
    ],
    'session_storage' => [
        'type' => 'SessionArrayStorage',
        'options' => [], // Only there for reference
    ],
    'session_manager' => [
        'enable_default_container_manager' => true,
        'validators' => [
            'Zend\Session\Validator\RemoteAddr',
            'Zend\Session\Validator\HttpUserAgent'
        ],
    ],

    //
    // Navigation configuration
    //

    'navigation' => [
        'admin' => __DIR__ . '/navigation_admin.php',
        'user' => __DIR__ . '/navigation_client.php',
        'reseller' => __DIR__ . '/navigation_reseller.php'
    ]
];
