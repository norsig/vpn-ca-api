# Changelog

## 4.5.0 (...)
- add more tests
- restructure code

## 4.4.2 (2016-02-25)
- consider timezone for expiry/revoke datetime for certificates, 
  correctly convert to the timezone of the server
- remove EasyRsa2Ca from the config template, should not be used 
  in new setups

## 4.4.1 (2016-02-25)
- use external ArrayBearerValidator

## 4.4.0 (2016-02-24)
- refactoring and code cleanup
- add more tests
- switch to Bearer authentication from Basic Authentication to improve
  performance (*BREAKING CONFIG*)

## 4.3.1 (2016-02-22)
- give the tunnel a better name in `server.twig`
- put the IPv4 address in the IPv6 address as the last 64 bits
- remove `SimpleError`
- update dependencies

## 4.3.0 (2016-02-18)
- update `server.twig` template for new vpn-server-api
  - remove CCD
  - `keepalive 10 60`
  - disable IP pool, now handled by `client-connect` and `client-disconnect`
  - use `2000::/3` instead of `::/0` as default IPv6 route

## 4.2.3 (2016-02-15)
- fix default easy-rsa path

## 4.2.2 (2016-02-15)
- update `server.twig`

## 4.2.1 (2016-02-13)
- update `server.twig` to solve the IPv6 situation and remove port sharing

## 4.2.0 (2016-02-10)
- implement OpenSSL `index.txt` CA database parser and RO API so we can move 
  state away from the VPN User Portal

## 4.1.0 (2016-02-03)
- switch default CA backend to `EasyRSA3Ca`, `EasyRsa2Ca` will keep working,
  but should not be used for new deployments anymore
- use actual "valid_from" and "valid_to" datetimes in configuration files so 
  user knows when the configuration was created and when it expires
- **DEPRECATE** the use of 'timestamp' in the 'client.twig' template

## 4.0.4 (2016-01-21)
- small server configuration template update

## 4.0.3 (2016-01-14)
- cleanup the CA backends, make them more robust
- generate a cert and revoke it in the EasyRsa2Ca backend when running
  init to immediately have a valid CRL available

## 4.0.2 (2016-01-13)
- BUG: test for commonName length, it MUST not exceed 64
- do not copy the easy-rsa code over anymore for EasyRsa3, simplify 
  `EasyRsa3Ca` a lot
- implement basic logging to syslog for API calls
- update testing to also add authentication plugin as the logging 
  code requires an API user
- rename configuration keys for EasyRsa3Ca:
  - `sourcePath` -> `easyRsaPath`
  - `targetPath` -> `caPath`
- add `client-connect` and `client-disconnect` to server template
  as examples
 
## 4.0.1 (2016-01-06)
- fix small bug when generating server configuration

## 4.0.0 (2016-01-05)
- use YAML instead of INI for configuration
- implement EasyRsa3Ca backend
- fixes for Debian
- update default OpenVPN configuration files

## 3.0.4 (2015-12-23)
- update `client.twig` and `server.twig` with better defaults

## 3.0.3 (2015-12-21)
- update `server.twig` to suggest port sharing

## 3.0.2 (2015-12-16)
- update `server.twig` to fix iOS in default gateway configuration

## 3.0.1 (2015-12-11)
- fix some minor bugs

## 3.0.0 (2015-12-11)
- restructure and rename the project
- assorted cleanups
- **BREAKING**: the configuration format changed, now using 
  `[BasicAuthentication]` instead of `authUser` and `authPass`
- update README

## 2.0.0 (2015-11-29)
- refactor some code to make it easier to implement other CA backends and make 
  testing easier
- add license headers to source files
- implement unit testing for CertService
- change response coding to 400 from 403 in case a configuration with the 
  provided commonName already exists
- update dependencies to new `fkooman/rest-plugin-authentication`
- content negotiation to retrieve just the certificate information from the 
  REST service instead of OpenVPN config
- remove `remotes` configuration option, use template override instead
- implement NullCa for testing and to have already a CA in preparation for
  EasyRsa3Ca if we get around to implementing that one
- remove the PDO database, the CA already provides a database, no need to 
  duplicate that
- change configuration file layout:
  - introduce `[EasyRsa2Ca]` section with `targetPath` to replace 
    `easyRsaConfigPath`, default is `data/easy-rsa`
  - rename `[ca]` section to `[CA]`
  - introduce `caBackend` to select the CA backend, default is `EasyRsa2Ca`
  - remove `[PdoStorage]`
  - no longer have `remotes` in configuration file, but use template override
    now in `config/views/client.twig`

## 1.0.3 (2015-09-22)
- update default configuration
- update README

## 1.0.2 (2015-08-10)
- fix fkooman/tpl-twig version constraint in spec file

## 1.0.1 (2015-08-10)
- use `fkooman/tpl-twig`
- update README.md
- add instructions on how to generate IPv6 ULA address in server config file

## 1.0.0 (2015-07-20)
- initial release
