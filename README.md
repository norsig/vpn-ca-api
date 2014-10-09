# Introduction
This is a configuration generator for OpenVPN. It aims at providing a REST API
that makes it easy to manage client configuration files. It is possible to 
generate a configuration and revoke a configuration.

# Requirements
This service requires a system running PHP and easy_rsa. This software was 
tested on Fedora 20 with PHP, the PDO database abstraction and Apache.

    $ yum install php easy-rsa php-pdo

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

We need to figure out if these are really necessary or that they can be
resolved differently

    $ sudo setsebool -P httpd_unified 1

# Configuration
Now you can run the init script to initialize the configuration and database:

    $ sudo -u apache bin/vpn-cert-service-init

To generate the server configuration use the following. Please note that it
will take a **really long time** to generate the DH keys.

    $ sudo -u apache bin/vpn-cert-service-generate-server-config

The second command will generate a server configuration file that can be 
loaded in your OpenVPN server.

# API
The HTTP API currently supports three calls:

- Generate a new configuration file for a client
- Revoke a certificate
- Obtain the CRL

These calls can be performed using e.g. `curl`:

Generate a configuration:

    $ curl -X POST http://localhost/vpn-cert-service/api.php/user@example.org

Delete (revoke) a configuration:

    $ curl -X DELETE http://localhost/vpn-cert-service/api.php/user@example.org

Obtain the CRL:

    $ curl http://localhost/vpn-cert-service/api.php/crl

# Docker
It is possible to use Docker to evaluate this service, see the `docker` folder.

# Testing
A comprehensive testing suite is included for validating the software. You can
run it using [PHPUnit](https://phpunit.de).

    $ /path/to/phpunit tests

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
