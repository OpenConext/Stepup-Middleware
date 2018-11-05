# Changelog

## Develop
**New features**
* The previously hardcoded "server_version" config option (Doctrine DBAL) is now configurable

## FGA (fine grained authorization)
The new fine grained authorization logic will allow Ra's from other institutions to accredidate RA's on behalf of another organisation.
This is determined based on the institution configuration.
https://github.com/OpenConext/Stepup-Deploy/wiki/rfc-fine-grained-authorization/b6852587baee698cccae7ebc922f29552420a296

* New institution configuration options can be configured (useRa, useRaa and selectRaa) #232 #233
* Update the institution configuration projections with the new FGA settings #235 #236
* Update middleware to work with the new Fine Grained AuthorizationContext #239 #240 #241 #242 #243 #244 #245 #248 #249 #250
* Update identity aggregate root to enhance the bounded context with RA info for multiple institutions #246 #247 #251 #254
* Update auditlog to enhance the logs with additional ra institution data #252 #253

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
