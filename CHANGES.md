# Changelog

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
