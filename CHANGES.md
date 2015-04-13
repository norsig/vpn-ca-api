# Changelog

## 0.2.1
- update `fkooman/rest` and authentication plugin

## 0.2.0
- update `fkooman/rest` and `fkooman/rest-plugin-basic` dependencies

## 0.1.21
- update `fkooman/rest` and `fkooman/rest-plugin-basic` dependencies

## 0.1.20
- require `fkooman/rest-plugin-basic` version 0.3.1 to support PHP 5.3
  again

## 0.1.19
- update `php-lib-rest-plugin-basic` to 0.3.0 and use its new API

## 0.1.18
- only add the `config/views` directory to the template directory 
  if it actually exists

## 0.1.17
- make it possible to override the client and server templates
  by putting them in `config/views` folder
- undo the `fragment` and `mssfix` settings. If they are really 
  needed they can be used by overriding the template

## 0.1.16
- add `fragment 1300` and `mssfix` to both the server and client 
  configuration by default
- remove `mlock` from client config

## 0.1.15
- the `vpn-cert-service-generate-server-config` script now requires a 
  parameter to specify the CN, this used to always be `server`, but this
  way it becomes possible to generate multiple server configurations all
  under the same CA
- enable the CRL again by default in the generated server config
- `topology` is now `subnet` in generated server config (issue #14)
- no longer specify the IP ranges the server provides to the clients in
  the configuration file as this is different for all servers anyway
- **BREAKING**: the configuration file now use the `servers` section name 
  instead of the `server` section name
- the `remotes` in the configuration file also show now the port and 
  protocol in the example, this is not strictly required if you use udp and
  port 1194, but for everything else it is, this demonstrates how to do it
- add `remote-random` to default client configuration

## 0.1.14
- use `gmdate()` instead of `date()` to determine last modified date of CRL
- disable mlock (capabilities issue when running as openvpn user) and 
  crl-verify (breaks openvpn when no CRL is available) by default in server 
  config

## 0.1.13
- fix DH generation file name, it would always write to `dh2048.pem` which is 
  confusing

## 0.1.12
- make NTP server configurable as a DHCP push option
- allow for configuring a different key size
- update dependencies
- set default key size to 3072 bits
- modify the default server and client configurations
- enable the CRL by default, server will fail to start without
- disable netbios by default in DHCP push option

## 0.1.11
- fix php 5.3 support

## 0.1.10
- update default config for certs to only be valid for 1 yr
- update RPM to create a config file only readable by the web server
- only require PHP >= 5.3.3

## 0.1.9
- update to new `fkooman/ini` API

## 0.1.8
- use new `fkooman/ini` instead of `fkooman/config`
- update coding standards

## 0.1.7
- update to new `fkooman/rest`
- add `Last-Modified` header to `ca.crl` response
- fix not needing authentication to fetch the CRL
- support HEAD request to CRL providing both `Last-Modified` and 
  `Content-Length` headers

## 0.1.6
- add error_handler that throws exceptions instead of showing PHP errors
- add error_log for all non-client exceptions (writes to Apache error_log)
- use `fkooman/rest-plugin-basic` for authentication
- update `fkooman/rest` dependency

## 0.1.5
- make the CRL accessible without authentication (issue #8)
- rename the CRL to `ca.crl` instead of just `crl`.

## 0.1.4
- update `fkooman/rest` and update code for API changes
- `CertService` now extends `Service` so all calls are not implemented
  in `CertService` and not in the `api.php` script
- path changed from `/api.php/` to `/api.php/config/` for generating and 
  deleting configurations (CRL path unchanged)
- the POST request now expects the commonName in the POST body, not in the 
  URI, see `README.md` for updated `curl` examples
- implement Basic Authenication (issue #4)

## 0.1.3
- implement tls-auth for DoS prevention in client and server config
- drop privileges to openvpn:openvpn after startup in server config

## 0.1.2
- add Docker files to evaluate vpn-cert-service
- add DNS and keepalive options to server config
- make vpn-cert-service-generate-server-config quiet, only output the config
- use fkooman/rest 0.4.11 for better exception handling
- remove the need for `openssl` cli invocation

## 0.1.1
- fix HTTP response to CRL endpoint when CRL is missing
- implement generating server configuration using a bin script
- **CONFIG UPDATE** - the config file format changed, see `config.ini.default` 
  for the new format

## 0.1.0
- initial release
