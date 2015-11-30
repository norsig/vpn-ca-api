# Changelog

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
