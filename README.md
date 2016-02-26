[![Build Status](https://travis-ci.org/eduVPN/vpn-config-api.svg)](https://travis-ci.org/eduVPN/vpn-config-api)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eduVPN/vpn-config-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eduVPN/vpn-config-api/?branch=master)

# Introduction

This is a CA for OpenVPN. It aims at providing a REST API that makes it easy to 
manage certificates.

# Deployment

See the [documentation](https://github.com/eduVPN/documentation) repository.

# Development

    $ composer install
    $ cp config/config.yaml.example config/config.yaml

Modify `config/config.yaml`. You can generate an API key with `openssl`:

    $ openssl rand -hex 16

Now continue:

    $ mkdir data
    $ php bin/init
    $ php -S localhost:8080 -t web/

# API
Issue a certificate:

    $ curl -H "Authorization: Bearer abcde" -d 'common_name=foo_bar' http://localhost:8080/api.php/certificate/

Revoke a certificate:

    $ curl -H "Authorization: Bearer abcde" -X DELETE http://localhost:8080/api.php/certificate/foo_bar

Get a list of certificates:

    $ curl -H "Authorization: Bearer abcde" http://localhost:8080/api.php/certificate/

Or for a particular user:

    $ curl -H "Authorization: Bearer abcde" http://localhost:8080/api.php/certificate/foo

Obtain the CRL:

    $ curl -H "Authorization: Bearer abcde" http://localhost:8080/api.php/ca.crl

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
