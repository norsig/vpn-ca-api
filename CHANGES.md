# Changelog

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
