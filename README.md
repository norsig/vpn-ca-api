[![Build Status](https://travis-ci.org/eduVPN/vpn-config-api.svg)](https://travis-ci.org/eduVPN/vpn-config-api)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eduVPN/vpn-config-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eduVPN/vpn-config-api/?branch=master)

# Introduction

This is a configuration generator for OpenVPN. It aims at providing a REST API
that makes it easy to manage client configuration files. It is possible to 
generate a configuration and revoke a configuration.

# Production

See the [documentation](https://github.com/eduVPN/documentation) repository.

# Development

## Installation

    $ cd /var/www
    $ sudo mkdir vpn-config-api
    $ sudo chown fkooman.fkooman vpn-config-api
    $ git clone https://github.com/eduVPN/vpn-config-api.git
    $ cd vpn-config-api
    $ /path/to/composer.phar install
    $ mkdir -p data
    $ sudo chown -R apache.apache data
    $ sudo semanage fcontext -a -t httpd_sys_rw_content_t '/var/www/vpn-config-api/data(/.*)?'
    $ sudo restorecon -R /var/www/vpn-config-api/data
    $ cp config/config.ini.defaults config/config.ini
    $ mkdir config/views
    $ cp views/* config/views
    $ sudo setsebool -P httpd_unified 1

## Configuration
Optionally, modify `config/config.ini`.

Now you can run the init script to initialize the CA:

    $ sudo -u apache bin/init

To generate the server configuration for use in your OpenVPN server use the 
following. Please note that it will take a **really** long time to generate the
DH keys.

    $ sudo -u apache bin/server-config vpn.example.org

To update the password, use this command to generate a new hash:

    $ php -r "require_once 'vendor/autoload.php'; echo password_hash('s3cr3t', PASSWORD_DEFAULT) . PHP_EOL;"

You should also update at least `config/views/client.twig` to specify the 
correct server to connect to as this template will be used for all client 
configuration downloads.

### Apache

The following configuration can be used in Apache, place it in 
`/etc/httpd/conf.d/vpn-config-api.conf`:

    Alias /vpn-config-api /var/www/vpn-config-api/web

    <Directory /var/www/vpn-config-api/web>
        AllowOverride None
        Require local
        SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
    </Directory>

# API
The HTTP API currently supports three calls:

- Generate a new configuration file for a client
- Revoke a configuration
- Obtain the CRL

These calls can be performed using e.g. `curl`:

Generate a configuration (using HTTP POST):

    $ curl -u admin:s3cr3t -d 'commonName=user@example.org' http://localhost/vpn-config-api/api.php/config/

Delete (revoke) a configuration:

    $ curl -u admin:s3cr3t -X DELETE http://localhost/vpn-config-api/api.php/config/user@example.org

Get a list of configurations:

    $ curl -u admin:s3cr3t http://localhost/vpn-config-api/api.php/config

Or for a particular user:

    $ curl -u admin:s3cr3t http://localhost/vpn-config-api/api.php/config?userId=foo

Obtain the CRL:

    $ curl http://localhost/vpn-config-api/api.php/ca.crl

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
