# Introduction
This is a configuration generator for OpenVPN. It aims at providing a REST API
that makes it easy to manage client configuration files. It is possible to 
generate a configuration and revoke a configuration.

# Requirements
This service requires a system running PHP and easy_rsa. This software was 
tested on Fedora 20 with PHP, the PDO database abstraction and Apache.

    $ yum install php easy-rsa php-pdo openvpn

The software was designed to run with SELinux enabled. RPM packages are 
provided for Fedora and CentOS (RHEL).

# Installation
It is recommended to use the RPM of this software to install it.

However, if you want to develop for the software or install it from source, 
these are the steps. Make sure you have [Composer](https://getcomposer.org) to 
install the dependencies.

    $ cd /var/www
    $ sudo mkdir vpn-cert-service
    $ sudo chown fkooman.fkooman vpn-cert-service
    $ git clone https://github.com/fkooman/vpn-cert-service.git
    $ cd vpn-cert-service
    $ /path/to/composer.phar install
    $ mkdir -p data
    $ sudo chown -R apache.apache data
    $ sudo semanage fcontext -a -t httpd_sys_rw_content_t '/var/www/vpn-cert-service/data(/.*)?'
    $ sudo restorecon -R /var/www/vpn-cert-service/data
    $ cd config
    $ cp config.ini.defaults config.ini

For now it is needed to set the following SELinux boolean:

    $ sudo setsebool -P httpd_unified 1

More work is needed to figure out if we can do without this...

# Configuration
Now you can run the init script to initialize the configuration and database:

    $ sudo -u apache bin/vpn-cert-service-init

To generate the server configuration use the following. Please note that it
will take a **really long time** to generate the DH keys.

    $ sudo -u apache bin/vpn-cert-service-generate-server-config

The second command will generate a server configuration file that can be 
loaded in your OpenVPN server.

You also need to store a hashed password for protecting the HTTP interface in
`config/config.ini`. The default password is `s3cr3t`. You can generate your
own by using the `bin/vpn-cert-service-generate-password-hash yourpass`. 

**NOTE**: generate your own hash and put it in `config/config.ini`, do **NOT** 
use the default.

# Apache
The following configuration can be used in Apache, place it in 
`/etc/httpd/conf.d/vpn-cert-service.conf`:

    Alias /vpn-cert-service /var/www/vpn-cert-service/web

    <Directory /var/www/vpn-cert-service/web>
        AllowOverride None

        <IfModule mod_authz_core.c>
          # Apache 2.4
          Require local
        </IfModule>
        <IfModule !mod_authz_core.c>
          # Apache 2.2
          Order Deny,Allow
          Deny from All
          Allow from 127.0.0.1
          Allow from ::1
        </IfModule>
    </Directory>

This will only allow access from `localhost`. This service MUST NOT be 
accessible over the Internet!

# API
The HTTP API currently supports three calls:

- Generate a new configuration file for a client
- Revoke a certificate
- Obtain the CRL

These calls can be performed using e.g. `curl`:

Generate a configuration:

    $ curl -u admin:s3cr3t -X POST -d 'user@example.org' http://localhost/vpn-cert-service/api.php/config/

Delete (revoke) a configuration:

    $ curl -u admin:s3cr3t -X DELETE http://localhost/vpn-cert-service/api.php/config/user@example.org

Obtain the CRL:

    $ curl http://localhost/vpn-cert-service/api.php/ca.crl

# Docker
It is possible to use Docker to evaluate this service, see the `docker` folder.

# Testing
A comprehensive testing suite is included for validating the software. You can
run it using [PHPUnit](https://phpunit.de).

    $ /path/to/phpunit tests

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
