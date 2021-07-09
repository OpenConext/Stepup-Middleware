# Changelog

# 4.3.0
**Feature**
* Add console command for migrating tokens to a new user/institution
* Clean documents numbers from eventstream

# 4.2.2
**Bugfix**
* Document-numbers were not removed from the event-stream (introduced in 4.2.0)

# 4.2.1
**Improvements**
* Install Symfony dependencies security upgrades

# 4.2.0
**Feature**
* Self Vetting (allow token registration from SelfService)

# 4.1.4
**Feature**
* Make token name in emails configurable

# 4.1.3
**Bugfix**
* Allow authorization filtering on Select RAA #315

# 4.1.2
**Bugfix**
* Component_info was not added in archive

# 4.1.1
**Chores**
 * Add the component info file #312
 * Implement depencency injection on console commands #313 
 
# 4.1.0
**Feature**
 * Make prove possession step optional #309
 
# 4.0.0
From this version PHP 7.2 is supported and support for PHP 5.6 is dropped.

Be aware that the new Symfony directory structure is now used. So if you are overwriting for example config files it is recommended 
to verify the location on forehand. Also the file extensions of Yaml files are changed and some Symfony specific special characters    
need to be escaped. 

See:  https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md

**Improvements** 
* Upgrade to Symfony4.4 LTS with PHP7.2 support #307

# 3.1.8
**Feature**
* Added identity & token bootstrap console commands (for test) #302 #303 #304 #305 

# 3.1.7
Drop RaCandidate projection in favour of dedicated query.
This is done in order to be able to push large institution configuration changes because the projection doesn't have to get updated for all possible candidates. Before this change this resulted in an OOM exception.

# 3.1.6
**Bugfix**
 * Disable ra-candidate fulltext search #300
 * Show the correct RA candidates for the virtual institution use case #299

# 3.1.5
**Bugfix**
 * Fix invalid RA candidate authorization #298

# 3.1.4
**Bugfix**
 * Add missing institution to the filter options #297

# 3.1.3
**Bugfix**
 * Allow language switching from SelfService #296
 
# 3.1.2
**Bugfix**
 * Allow RA commands in RA environment #295
 
## 3.1.1
**Bugfix**
 * Add some missing institution fields to migration #293

## 3.1.0
A release with bugfixes after initial FGA tests
 * Fix sho mixed casing once and for all #291
 * Use configured institutions for institution lists #292
 * Enforce use_raa even if user is raa through select_raa #290
 * Security upgrades #289

## 3.0.2
**Bugfix**
The composer lockfile was not in sync with the changes in composer.json.

## 3.0.1
This is a security release that will harden the application against CVE 2019-346
 * Upgrade Stepup-saml-bundle to version 4.1.8 #286

## 3.0.0 FGA (fine grained authorization)
The new fine grained authorization logic will allow Ra's from other institutions to accredidate RA's on behalf of another organisation.
This is determined based on the institution configuration.
https://github.com/OpenConext/Stepup-Deploy/wiki/rfc-fine-grained-authorization/b6852587baee698cccae7ebc922f29552420a296

**New features**
* New institution configuration options can be configured (useRa, useRaa and selectRaa) #232 #233
* Update the institution configuration projections with the new FGA settings #235 #236
* Update middleware to work with the new Fine Grained AuthorizationContext #239 #240 #241 #242 #243 #244 #245 #248 #249 #250 #280 #281 #279 #278 #274 #272 #270 #271 #269 #268 #267 #266 #265 #264 #263 #260 #259 #257 #255
* Update identity aggregate root to enhance the bounded context with RA info for multiple institutions #246 #247 #251 #254
* Update auditlog to enhance the logs with additional ra institution data #252 #253
* The previously hardcoded "server_version" config option (Doctrine DBAL) is now configurable

**Improvements**
* Install security upgrades

**Backwards compatibility breaking changes**
The introduction of the FGA changes resulted in new versions of serveral events. This complicates reverting to an onlder version of Stepup-Middleware after applying one of these new events. Also, existing projections have been updated (ra_listing and ra_candidates) introducing further complications when rolling back to a previous version.

See individual stories and commits for more details.

**Bugfix**
* Fix RA removal when token gets removed #284
* Whitelist missing toString methods #282

## 2.9.4
This is a security release that will harden the application against CVE 2019-346
 * Upgrade Stepup-saml-bundle to version 4.1.8 #286

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
