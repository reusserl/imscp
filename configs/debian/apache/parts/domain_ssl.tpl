<VirtualHost {DOMAIN_IPS}>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    LogLevel error
    ErrorLog {HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log

    DocumentRoot {DOCUMENT_ROOT}

    DirectoryIndex index.html index.xhtml index.htm

    Alias /errors/ {HOME_DIR}/errors/

    # SECTION itk BEGIN.
    AssignUserID {USER} {GROUP}
    # SECTION itk END.
    # SECTION suexec BEGIN.
    SuexecUserGroup {USER} {GROUP}
    # SECTION suexec END.

    # SECTION php_enabled BEGIN.
    DirectoryIndex index.php

    # SECTION php_fpm BEGIN.
    # SECTION mod_fastcgi BEGIN.
    Alias /php-fcgi /var/lib/apache2/fastcgi/php-fcgi-{DOMAIN_NAME}-ssl
    FastCGIExternalServer /var/lib/apache2/fastcgi/php-fcgi-{DOMAIN_NAME}-ssl \
        -{FASTCGI_LISTEN_MODE} {FASTCGI_LISTEN_ENDPOINT} \
        -idle-timeout 900 \
        -pass-header Authorization
    # SECTION mod_fastcgi END.

    # SECTION mod_proxy_fcgi BEGIN.
    SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1

    <Proxy "{PROXY_FCGI_PATH}{PROXY_FCGI_URL}" retry=0>
        ProxySet connectiontimeout=5 timeout=7200
    </Proxy>

    <FilesMatch \.ph(p[3457]?|t|tml)$>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} -f
        RewriteRule .* - [H=proxy:{PROXY_FCGI_URL},NC]
    </FilesMatch>
    # SECTION mod_proxy_fcgi END.
    # SECTION php_fpm END.
    # SECTION php_enabled END.
    # SECTION mod_proxy_fcgi END.
    # SECTION php_fpm END.
    # SECTION php_enabled END.

    <Directory {HOME_DIR}>
        Options +SymLinksIfOwnerMatch
        {AUTHZ_ALLOW_ALL}
    </Directory>

    <Directory {DOCUMENT_ROOT}>
        # SECTION php_disabled BEGIN.
        AllowOverride AuthConfig Indexes Limit Options=Indexes \
            Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule
        # SECTION php_disabled END.
        # SECTION php_enabled BEGIN.
        AllowOverride All
        # SECTION fcgid BEGIN.
        Options +ExecCGI
        FCGIWrapper {PHP_FCGI_STARTER_DIR}/{FCGID_NAME}/php-fcgi-starter
        # SECTION fcgid END.
        # SECTION itk BEGIN.
        php_admin_value open_basedir "{HOME_DIR}/:{PEAR_DIR}/:/dev/random:/dev/urandom"
        php_admin_value upload_tmp_dir {TMPDIR}
        php_admin_value session.save_path {TMPDIR}
        php_admin_value soap.wsdl_cache_dir {TMPDIR}
        php_admin_value sendmail_path "/usr/sbin/sendmail -t -i -f webmaster@{EMAIL_DOMAIN}"
        # Custom values
        php_admin_value max_execution_time {MAX_EXECUTION_TIME}
        php_admin_value max_input_time {MAX_INPUT_TIME}
        php_admin_value memory_limit "{MEMORY_LIMIT}M"
        php_flag display_errors {DISPLAY_ERRORS}
        php_admin_value post_max_size "{POST_MAX_SIZE}M"
        php_admin_value upload_max_filesize "{UPLOAD_MAX_FILESIZE}M"
        php_admin_flag allow_url_fopen {ALLOW_URL_FOPEN}
        # SECTION itk END.
        # SECTION php_enabled END.
    </Directory>

    # SECTION cgi_support BEGIN.
    Alias /cgi-bin {WEB_DIR}/cgi-bin

    <Directory {WEB_DIR}/cgi-bin>
        DirectoryIndex index.cgi index.pl
        AllowOverride AuthConfig Indexes Limit Options=Indexes
        AddHandler cgi-script .cgi .pl
        Options +ExecCGI
    </Directory>
    # SECTION cgi_support END.

    # SECTION php_disabled BEGIN.
    # SECTION itk BEGIN.
    php_admin_flag engine off
    # SECTION itk END.
    # SECTION fcgid BEGIN.
    RemoveHandler .php .php3 .php4 .php5 .php7 .pht .phtml
    # SECTION fcgid END.
    # SECTION php_fpm BEGIN.
    RemoveHandler .php .php3 .php4 .php5 .php7 .pht .phtml
    # SECTION php_fpm END.
    # SECTION php_disabled END.

    # SECTION addons BEGIN.
    # SECTION addons END.

    SSLEngine On
    SSLCertificateFile {CERTIFICATE}
    SSLCertificateChainFile {CERTIFICATE}

    # SECTION hsts BEGIN.
    Header always set Strict-Transport-Security "max-age={HSTS_MAX_AGE}{HSTS_INCLUDE_SUBDOMAINS}"
    # SECTION hsts END.

    Include {HTTPD_CUSTOM_SITES_DIR}/{DOMAIN_NAME}.conf
</VirtualHost>
