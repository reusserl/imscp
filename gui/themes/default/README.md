# Distribution files (i-MSCP default theme)

## Introduction

The distribution files are used in production environments where development mode is disabled. The intent is to minimize
server-load and the page-load time by concatenation and minification of html, css and js sources.

### Howto build distribution files

### Setup environment

#### node.js installation

As root user, run the following commands:

```shell
# cd /usr/local/src
# wget -N http://nodejs.org/dist/latest/node-v5.0.0-linux-x64.tar.gz
# tar -xzf node-v5.0.0-linux-x64.tar.gz 
# cd node-v*-linux-x64/
# cp -rp bin/ include/ lib/ share/ /usr/local
```

#### Grunt CLI installation

As root user, run the following command:

```shell
# npm install -g grunt-cli
```

#### Project dependencies installation

As normal user, run the following commands:

```shell
$ cd <project_path>/gui/themes/default
$ npm install
```

### Build distribution files

As normal user, run the following command:

```shell
$ grunt build
```

### Commit your changes

Once the new distribution files are built, you must not forget to update the **THEME_ASSETS_VERSION** parameter in the
**imscp.conf** configuration file and commit your change on GitHub.

### Release process

All the procedure above must be part of the release process. Before releasing a new i-MSCP version, you must ensure that
the distribution files are synchronized with the source files by running the grunt build task as explained above. This
procedure will be integrated in the release script as soon as possible.
