i-MSCP Dev module
=================

This module allows to enable/disable development mode through the i-MSCP
FrontEnd command line tool.

To enable development mode
--------------------------

```sh
cd /var/www/imscp/gui/bin
php imscp.php imscp:development:mode enable
```

**Note:** Enabling development mode will also clear your module configuration
cache, to allow safely updating dependencies and ensuring any new configuration
is picked up by your application.

To disable development mode
---------------------------

```sh
cd /var/www/imscp/gui/bin
php imscp.php imscp:development:mode disable
```

**Note:** Don't run development mode on your production server.
