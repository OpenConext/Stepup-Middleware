# Changelog

## 2.9.3
**Bugfix**
* Be less specific in which validator is used in InstitutionConfigurationController, this is correctly configured in the
  application configuration. This to prevent cache warming issues in production.

## 2.9.2
**Improvements**
* The previously hardcoded "server_version" config option (Doctrine DBAL) is now configurable
* Add missing trusted_proxies-setting #231 thanks @tvdijen
* Use DI in managment bundle controllers #237
* Improved Behat test support 

## 2.9.1
**Bugfix**
* Resolve a Doctrine DBAL configuration issue described in https://github.com/doctrine/DoctrineBundle/issues/351

## 2.9.0
**Bugfix**
* Fix SRAA command for institution without config #228

**Improvements**
* Symfony 3.4.15 upgrade #230
* Behat test support #229
* Removed RMT from the project
* Improved the documentation fixing links and formatting
