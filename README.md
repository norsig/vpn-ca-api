[![Build Status](https://travis-ci.org/eduVPN/vpn-ca-api.svg)](https://travis-ci.org/eduVPN/vpn-ca-api)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eduVPN/vpn-ca-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eduVPN/vpn-ca-api/?branch=master)

# Introduction

This is a CA for OpenVPN. It aims at providing a REST API that makes it easy to 
manage certificates.

# Deployment

See the [documentation](https://github.com/eduVPN/documentation) repository.

# Development

    $ composer install
    $ cp config/config.yaml.example config/config.yaml

Update the `caPath` to point to a writable directory, for example the `data`
directory under the current folder.

    $ mkdir data
    $ php bin/init
    $ php -S localhost:8008 -t web/

# Authentication

The API is protected using Bearer tokens. There are various "clients" 
configured as can be seen in the configuration file together with their 
permissions. See `config/config.yaml` for the defaults.

## API

Issue a client certificate:

    $ curl -H "Authorization: Bearer abcdef" -d 'common_name=foo_bar&cert_type=client' http://localhost:8008/api.php/certificate/

Issue a server certificate:

    $ curl -H "Authorization: Bearer aabbcc" -d 'common_name=vpn.example&cert_type=server' http://localhost:8008/api.php/certificate/

Revoke a certificate:

    $ curl -H "Authorization: Bearer abcdef" -X DELETE http://localhost:8008/api.php/certificate/foo_bar

Get a list of certificates:

    $ curl -H "Authorization: Bearer abcdef" http://localhost:8008/api.php/certificate/

Or for a particular user:

    $ curl -H "Authorization: Bearer abcdef" http://localhost:8008/api.php/certificate/foo

Obtain the CRL:

    $ curl -H "Authorization: Bearer aabbcc" http://localhost:8008/api.php/ca.crl

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
