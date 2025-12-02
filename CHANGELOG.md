# Changelog

# 7.0.0 - unreleased
**Notable Changes**
- Upgrade to Symfony 7.4 (from 6.4)
- Upgrade to PHPUnit 10 (from 9.x)
- Upgrade to PHPStan 2.0 (from 1.x)
- Migrate from annotations to attributes (PHP 8 attributes)

**Important for System Administrators**
Ensure the `database_server_version` parameter contains the correct MariaDB version. e.g. `10.6.23-MariaDB`


**Improvements**
- Upgrade all Symfony components to 7.4
- Upgrade Stepup-Bundle to 7.0
- Modernize codebase with PHP 8+ features
- Fix various Symfony and Doctrine deprecations
- Improved code quality with updated tooling

# 6.0.2
- Repair deprovisioning API calls
- Restore deprovisioned user on new login (returing users)

# 6.0.0
- Move to PHP 8.2
- Move to Synfony 6
- Upgrade composer dependencies 
- Bump saml2, xmlseclibs

# 5.2.0
- Some changes for the Docker release of Middleware. This app will get a PHP7.2 release in docker
- Migrate the github actions to reusable actions
- Bump phpseclib

# 5.1.1
- The changes from 5.0.10 got lost in release 5.1, in this version they are added back

# 5.1.0
- Introduction of the SSO on 2FA feature in Stepup-Middleware
- Please run the provided Doctrine Migration to prepare the Middleware and Gateway projections
- By default the SSO on 2FA feature is disabled for all institutions and can be enabled via the Middleware institution configuration

**Additional information**
- See docs/sso-on-2fa.md
- See docs/MiddlewareConfiguration.md
- See https://www.pivotaltracker.com/epic/show/5024251 for details

# 5.0.10
- Bugfix: ensure RA(A) authorisations are verified against the ra_listing projection
  this caused RAA authorisations to be available to RA users.
- BC: SelfService 3.5 was not compatible with Middleware 5.0.9. The self vet command changed
  from using the authoring second factor identifier to use the authoring loa. Underwater, the
  second factor identifier field already transported the loa, in version 5.0 that field was 
  renamed. In this release MW can support use of both fields. Version 5.1 should remove this
  support. #402

# 5.0.9
- Support self-vetting of self-asserted tokens #401

# 5.0.8
-  Set default value for vetted_second_factor vetting_type column #400

# 5.0.7
This release changes the way the Gateway Second Factor projection is identifying
if the SF

- Filter non identity-vetted RA candidates #397
- Update SAT projection #396

# 5.0.3 .. 5.0.6
- Add support for old 'vetting type-less' identity move event payload #393
- Make deprovision less strict #382
- Update documentation for recovery token API config #386
- Listen for recovery token possession events #387
- Fix misleading institution configuration error message #389

# 5.0.2
**Bugfix**
- Repair several mailer service issues #381
- Deprovision endpoint no longer returns FAILED when user already deprovisioned  

# 5.0.1
**Bugfix**
- Repair addressing in Swiftmailer 

# 5.0.0
**Self-asserted token registration**
- Create the self-asserted tokens feature toggle #353
- Self-asserted registration commands are handled #360
- Audit log now show recovery tokens #371
- Projections where updated #368
- Support recovery token interactions #370 #361 #358 #357  #356
- Authorization endpoint changes  #354 #372 #366 #365
- Support vetting type hints (creation and retrieval) #373
- Send recovery token mail messages #374

**Maintenance**
- Replace deprecated Swiftmailer with Symfony mailer #352

# 4.5.1
- Respond with OK when identity not found (deprovisioning) #375
- Several minor security and other improvements 

# 4.5.0
**Features:**
- Middleware was made compatible with User-Lifecycle #342 #343 #344 #346 #347 #348 #350
- Github Actions are used to run QA/CI tests #349

**Bugfix**
- RA/RAA MW command denied when user is not RA/RAA in home institution #351

**Maintenance:**
- Composer: prevent flex warning #337
- Composer package upgrades #341

# 4.4.1
- Add Github Actions tag automation workflow

# 4.4.0
**Feature**
* Add readonly API user #334
* Set strict sql_mode #335 #336
* Improve NameId input validation #335 #336

**Chore**
* Deprecate and remove u2f support #285
* Improve identity endpoint documentation #333

# 4.3.2
**Bugfix**
* Running the bootstrap yubikey with identity (create a SRAA) was not yet 
  creating an anonymous token, this caused the command to crash, as permissions
  are now checked more strictly (as of SF4). #332

# 4.3.1
**Bugfix**
* The migration that removes document numbers from the event_stream does not escape 
  the backslash characters in the payload (introduced in 4.3.0) #331

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
