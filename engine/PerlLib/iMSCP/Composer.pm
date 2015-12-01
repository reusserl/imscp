=head1 NAME

 iMSCP::Composer - i-MSCP Composer packages installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package iMSCP::Composer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::EventManager;
use iMSCP::Getopt;
use iMSCP::Dialog;
use Cwd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Composer packages installer for iMSCP.

=head1 PUBLIC METHODS

=over 4

=item registerRepository($type, $url)

=cut

sub registerRepository
{
	my ($self, $type, $url) = @_;

	defined $type or die('Missing repository $type parameter');
	defined $url or die('Missing repository $url parameter');

	push @{$self->{'repositories'}}, <<REPOSITORY;
		{
			"type": "$type",
			"url": "$url"
		},
REPOSITORY

	0;
}

=item registerPackage($package [, $packageVersion = 'dev-master', [$devonly = undef]])

 Register the given composer package for installation

 Param string $package Package name
 Param string $packageVersion OPTIONAL Package version
 Param bool $devonly OPTIONAL When set to true, indicate that the package is required in dev environment only
 Return 0

=cut

sub registerPackage
{
	my ($self, $package, $packageVersion, $devonly) = @_;

	defined $package or die('Missing $package parameter');
	$packageVersion ||= 'dev-master';

	unless($devonly) {
		push @{$self->{'required_packages'}}, <<PACKAGE;
	"$package": "$packageVersion",
PACKAGE
	} else {
		push @{$self->{'required_dev_packages'}}, <<PACKAGE;
	"$package": "$packageVersion",
PACKAGE
	}

	0;
}

=item registerPackages(\%packages)

 Register the given composer packages for installation

 Param hash \%packages
 Return 0

=cut

sub registerPackages
{
	my ($self, $packages) = @_;

	while(my ($package, $version) = each(%{$packages})) {
		$self->registerPackage($package, $version);
	}

	0;
}


=item registerAutoloaderMap($type, $namespace, $path)

 Register autoloader map

 Param hash \%packages
 Param string $type Autoloader mapping type (psr-0|psr-4)
 Param string $namespace Namespace
 Param string $path Path
 Return 0 on success, die on failure

=cut

sub registerAutoloaderMap
{
	my ($self, $type, $namespace, $path) = @_;

	defined $type or die('Missing autoloading $type parameter');
	defined $namespace or die('Missing autoloading $namespace parameter');
	defined $path or die('Missing autoloading $path parameter');

	if($type eq 'psr-0') {
		push @{$self->{'autoload_psr0'}}, <<AUTOLOAD_MAP;
		"$namespace": "$path",
AUTOLOAD_MAP
	} elsif($type eq 'psr-4') {
		push @{$self->{'autoload_psr4'}}, <<AUTOLOAD_MAP;
		"$namespace": "$path",
AUTOLOAD_MAP
	} else {
		die(sprintf('Unknown autoloader mapping type: %s', $type));
	}

	0;
}

=item installPackages()

 Install composer packages that were registered for installation

 Return int 0 on success, die on faiure;

=cut

sub installPackages
{
	my $self = shift;

	$ENV{'COMPOSER_HOME'} = "$self->{'pkgDir'}/.composer"; # Override default composer home directory
	$ENV{'COMPOSER_NO_INTERACTION'} = '1'; # Disable user interaction

	$self->_cleanPackageCache() if iMSCP::Getopt->cleanPackageCache;
	iMSCP::Dir->new( dirname => $self->{'pkgDir'} )->make();
	$self->_getComposer() unless iMSCP::Getopt->skipPackageUpdate && -x "$self->{'pkgDir'}/composer.phar";
	$self->_installPackages() unless iMSCP::Getopt->skipPackageUpdate && $self->_checkRequirements();

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Composer, die on failure

=cut

sub _init
{
	my $self = shift;

	$self->{'repositories'} = [];
	$self->{'required_packages'} = [];
	$self->{'required_dev_packages'} = [];
	$self->{'autoload_psr0'} = [];
	$self->{'autoload_psr4'} = [];
	$self->{'pkgDir'} = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages";
	$self->{'phpCmd'} = 'php -d allow_url_fopen=1 -d suhosin.executor.include.whitelist=phar';
	$self;
}

=item _getComposer()

 Get composer.phar

 Return 0 on success, die on failure

=cut

sub _getComposer
{
	my $self = shift;

	my $curDir = getcwd();
	chdir $self->{'pkgDir'} or die(sprintf('Could not change current directory to %s: %s', $self->{'pkgDir'}, $!));

	unless (-f "$self->{'pkgDir'}/composer.phar") {
		iMSCP::Dialog->getInstance()->infobox(<<EOF);

Installing composer.phar from http://getcomposer.org

Please wait, depending on your connection, this may take few seconds...
EOF

		my ($stdout, $stderr);
		execute("curl -s http://getcomposer.org/installer | $self->{'phpCmd'}", \$stdout, \$stderr) == 0 or die(
			sprintf('Could not install composer.phar: %s', $stderr || 'Unknown error')
		);
	} else {
		iMSCP::Dialog->getInstance()->infobox(<<EOF);

Updating composer.phar from http://getcomposer.org

Please wait, depending on your connection, this may take few seconds...
EOF
		my $rs = execute(
			"$self->{'phpCmd'} $self->{'pkgDir'}/composer.phar --no-ansi -d=$self->{'pkgDir'} self-update",
			\my $stdout, \my $stderr
		);
		debug($stdout) if $stdout;
		!$rs or die(sprintf('Could not update composer.phar: %s', $stderr || 'Unknown error'));
	}

	chdir $curDir or die(sprinf('Could not change directory to %s: %s', $curDir, $!));

	0;
}

=item _installPackages()

 Install or update packages

 Return 0 on success, die on failure

=cut

sub _installPackages
{
	my $self = shift;

	$self->_buildComposerFile();

	my $dialog = iMSCP::Dialog->getInstance();
	my $msgHeader = "\nInstalling/Updating required composer/pear packages\n\n";
	my $msgFooter = "\nPlease wait, depending on your connection, this may take few seconds...";

	# The update option is used here but composer will automatically fallback to install mode when needed
	# Note: Any progress/status info goes to stderr (See https://github.com/composer/composer/issues/3795)
	executeNoWait(
		"$self->{'phpCmd'} $self->{'pkgDir'}/composer.phar --no-ansi -d=$self->{'pkgDir'} update" .
			(!$main::imscpConfig{'DEVMODE'} ? ' --no-dev' : ''),
		sub { my $str = shift; $$str = '' },
		sub {
			my $str = shift;

			if($$str =~ /^$/m) {
				$$str = '';
			} else {
				my ($strBkp, $buff) = ($$str, '');
				$buff .= $1 while($$str =~ s/^(.*\n)//);

				if($buff ne '') {
					debug($buff);
					$dialog->infobox("$msgHeader$buff$msgFooter");
					$$str = $strBkp unless $strBkp =~ /^Updating dependencies.*\n/m;
				}
			}
		}
	) == 0 or die(sprintf('Could not install/update required composer/pear packages.'));
}

=item _buildComposerFile()

 Build composer.json file

 Return 0 on success, die on failure

=cut

sub _buildComposerFile
{
	my $self = shift;

	my $tpl = <<TPL;
{
    "name": "imscp/packages",
    "description": "i-MSCP composer packages",
    "license": "GPL-2.0+",
    "type": "metapackage",
    "repositores": [
{REPOSITORIES}
    ],
    "require": {
{REQUIRE}
    },
    "require-dev": {
{REQUIRE_DEV}
    },
    "config": {
        "preferred-install": "dist",
        "process-timeout": 2000,
        "classmap-authoritative": true,
        "optimize-autoloader": true,
        "discard-changes": true
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "autoload": {
        "psr-0": {
{AUTOLOAD_PSR0}
        },
        "psr-4": {
{AUTOLOAD_PSR4}
        }
    }
}
TPL

	my $file = iMSCP::File->new( filename => "$self->{'pkgDir'}/composer.json" );
	$file->set(process(
		{
			REPOSITORIES => (join('', @{$self->{'repositories'}}) =~ s/,+$//r),
			REQUIRE => (join('', @{$self->{'required_packages'}}) =~ s/,+$//r),
			REQUIRE_DEV => (join('', @{$self->{'required_dev_packages'}}) =~ s/,+$//r),
			AUTOLOAD_PSR0 => (join('', @{$self->{'autoload_psr0'}}) =~ s/,+$//r),
			AUTOLOAD_PSR4 => (join('', @{$self->{'autoload_psr4'}}) =~ s/,+$//r)
		},
		$tpl
	));
	$file->save();
}

=item _cleanPackageCache()

 Clear composer package cache

 Return 0 on success, die on failure

=cut

sub _cleanPackageCache
{
	my $self = shift;

	iMSCP::Dir->new( dirname => $self->{'pkgDir'} )->remove();
}

=item _checkRequirements()

 Check package version requirements

 Return bool TRUE if all requirements are meets, FALSE otherwise

=cut

sub _checkRequirements
{
	my $self = shift;

	return 0 unless -d $self->{'pkgDir'};

	# Check for required packages
	for(@{$self->{'required_packages'}}) {
		my ($package, $version) = $_ =~ /"(.*)":\s*"(.*)"/;
		my $rs = execute(
			"$self->{'phpCmd'} $self->{'pkgDir'}/composer.phar --no-ansi -d=$self->{'pkgDir'} show --installed " .
				escapeShell($package) . ' ' . escapeShell($version),
			\my $stdout, \my $stderr
		);
		debug($stdout) if $stdout;
		return 0 if $rs;
	}

	# Check for required development package only in development environment
	if($main::imscpConfig{'DEVMODE'}) {
		for(@{$self->{'required_dev_packages'}}) {
			my ($package, $version) = $_ =~ /"(.*)":\s*"(.*)"/;
			my $rs = execute(
				"$self->{'phpCmd'} $self->{'pkgDir'}/composer.phar --no-ansi -d=$self->{'pkgDir'} show --installed " .
					escapeShell($package) . ' ' . escapeShell($version),
				\my $stdout, \my $stderr
			);
			debug($stdout) if $stdout;
			return 0 if $rs;
		}
	}

	1;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
