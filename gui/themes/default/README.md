# i-MSCP default theme

This is the default theme for i-MSCP.

## Distribution files

File located in the `./dist` directory are distribution files that are used in production environments. The intent is to
minimize server-load and the page-load time by concatenating and minifying html, css and js sources.

### Howto build distribution files

#### Setup environment

##### Node.js installation

As root user, run the following commands:

```shell
# cd /usr/local/src
# wget -N http://nodejs.org/dist/latest/node-v5.0.0-linux-x64.tar.gz
# tar -xzf node-v5.0.0-linux-x64.tar.gz 
# cd node-v*-linux-x64
# cp -rp bin/ include/ lib/ share/ /usr/local
```

##### Grunt CLI installation

As root user, run the following command:

```shell
# npm install -g grunt-cli
```

##### Dependencies installation

As normal user, run the following commands:

```shell
$ cd <project_path>/gui/themes/default
$ npm install
```

#### Build distribution files

As normal user, run the following command:

```shell
$ grunt build
```

If all goes fine, you should get output such as:

![Grunt success]
(https://raw.githubusercontent.com/i-MSCP/imscp/aps-standard/gui/themes/default/grunt.png)

#### Commit the new distribution files

Once the new distribution files have been generated in the `./dist` directory, you must add them and commit them on GitHub.
Don't forget that each time your run the `grunt build` command, new asset file revisions are created, meaning that you'll
have to re-add them and delete the old-ones.

#### Release process

All the procedure above must be part of the release process. Before releasing a new i-MSCP version, you must ensure that
the distribution files are synchronized with the source files by running the grunt build task as explained above. This
procedure will be integrated in the release script as soon as possible.
