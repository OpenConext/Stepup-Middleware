Step-up Middleware
==================

[![Build Status](https://travis-ci.org/OpenConext/Stepup-Middleware.svg)](https://travis-ci.org/OpenConext/Stepup-Middleware) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/OpenConext/Stepup-Middleware/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/OpenConext/Stepup-Middleware/?branch=develop)

This component is part of "Step-up Authentication as-a Service". See [Stepup-Deploy](https://github.com/OpenConext/Stepup-Deploy) for an overview and installation instructions for a complete Stepup system, including this component. The requirements and installation instructions below cover this component only.

## Requirements

 * PHP 8.2
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 10.6
 * A working [Gateway](https://github.com/OpenConext/Stepup-Gateway)

## Installation

Clone the repository or download the archive to a directory. Install the dependencies by running `composer install` and fill out the database credentials et cetera.

Make sure to run database migrations using `bin/console doctrine:migrations:migrate`.

When using 'Stepup-Deploy' the 'deploy' entity manager should be used in order to use the correct credentials e.g. `bin/console doctrine:migrations:migrate --em=deploy`

## Management API

Some of the configuration of the components is static (i.e. stored in parameteres.yml). The configuration that is expected to change during the operation of a Stepup system is managed through an API on the middleware. This provides  one place and action to change the configuration and allows changing of this configuration without having to modify the configuration of several components on several servers.

- The API calls are documented in the [middleware API documentation](./docs/MiddlewareManagementAPI.md).
- The configuration itself is elaborate and is described in detail in the [Middlware configuration](./docs/MiddlewareConfiguration.md).
- The ansible Stepup-Middleware role write scripts in /opt/stepup/  for pushing the configuration to the middleware component

## Development Notes

### Technical debt
 * https://github.com/broadway/event-store-dbal blocks upgrade to `doctrine/dbal:^4.0`

### Adding new events

Whenever adding a new event, be sure to update `bin/config/events.yml`.
This is a list of events that is shown when replaying events.
Also be sure to create or update the event serialization/deserialization tests,
for example see [EventSerializationAndDeserializationTest for Configuration events][event-serialization-example]

### Mocking time

Due to a limitation of mocking of static methods, to mock time, the helper `DateTimeHelper::stubNow(DateTime $now)` was
created. Call `::stubNow($now)` to set a fixed date/time, and call `::stubNow(null)` to disable stubbing. It is
recommended to run tests in a separate process when using this helper so the stub value doesn't persist between tests.

```php
/** @runTestInSeparateProcess */
public function testItWorks()
{
    # Trick `DateTime::now()` into thinking it is 1970.
    DateTimeHelper::stubNow(new DateTime('@0'));

    $this->assertEquals('1970-01-01T00:00:00+00:00', (string) \Surfnet\Stepup\DateTime\DateTime::now());
}
```

### Middleware vs Gateway projections
You might have seen that both the Gateway and Middleware have databases of their own. Gateway has very little
knowledge of any Middleware business logic. However some data is required in Gateway for smooth operation. For example
we want to verify in Gateway if an institution is whitelisted. Doing an API call for each gateway interaction would 
be more costly than having this data projected in the Gateway database. 

At this point four Gateway projections exist. Note that they are exclusively managed by Middleware! 

Results from a Middleware event might result in an update of a Gateway projection.

#### Creating a Gateway projection
Middleware uses Doctrine for ORM and DBAL implementation. Middleware is configured with a multi entity manager setup.
Three EntityManagers (EM) are known: middleware (default), gateway and deploy. Each have a different user with each his
own privileges.

Note that when you want to do an interaction on a specific EM, you need to specifically instruct Symfony/Doctrine to do
so. This becomes apparent when creating and running Doctrine Migrations. Say you want to add a field to the 
`whitelist_entry` Entity. Simply running:

```shell
$ ./bin/console doctrine:migrations:diff 
```

Does not result in a new Migration file containing the whitelist entity change. In order to get that change to show up,
you need to explicitly instruct use of the correct Entity Manager.

```shell
$ ./bin/console doctrine:migrations:diff --em=gateway
```

The resulting migration is not yet ready to go. The migration file itself needs to be marked to use the correct database 
schema. The following snippet from a Migration shows how to achieve this goal.

```php
// Excerpt from Version20220519134637
$gatewaySchema = $this->getGatewaySchema();
$this->addSql(sprintf('ALTER TABLE %s.second_factor ADD vetting_type VARCHAR(255) NOT NULL', $gatewaySchema));
```

#### Keep entities in sync
Now here comes the tricky bit. Both Gateway and Middleware have a view on the projection. Middleware writes to the 
gateway schema. And Gateway reads the data. Both projects utilize Doctrine to achieve those goals. Needless to say
the Entity definitions for the entity in question needs to be synchronized. If they are not, weird errors may occur.

For example see [this PR](https://github.com/OpenConext/Stepup-Gateway/pull/123/commits/4ec910f22c9b2dd0347dda2ae0f855a50bd43e64)

### Adding support for a new Generic SAML Second Factor `biometric`, by example

 * https://github.com/OpenConext/Stepup-bundle/pull/31/commits/55279033a7f4e261277008603d9be94ebb582469
 * Release a new minor version of `surfnet/stepup-bundle`.
 * https://github.com/OpenConext/Stepup-Middleware/pull/106/commits/c3b42c92593f10587f9e0051420e711c974dd319
 * https://github.com/OpenConext/Stepup-SelfService/pull/96/commits/efa7feb29f0ee26d0d9860849f3f379131ba23cd
 * https://github.com/OpenConext/Stepup-RA/pull/102/commits/f2c0d4f57912a6c026c58db2818735bacf7a7787
 * https://github.com/OpenConext/Stepup-Gateway/pull/90/commits/1463cf05d1bec9e5e1fa1103b81fa6ada00a611f
 * Add the Self-Service and RA applications to the `gssp_allowed_sps` parameters:
```yaml
gssp_allowed_sps:
   - (...)
   - 'https://ss-dev.stepup.coin.surf.net/app_dev.php/registration/gssf/biometric/metadata'
   - 'https://ra-dev.stepup.coin.surf.net/app_dev.php/vetting-procedure/gssf/biometric/metadata'
```
 * Configure these SPs through the Middleware configuration API.

## Release strategy
Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management fro more information on the release strategy used in Stepup projects.

[event-serialization-example]: src/Surfnet/Stepup/Tests/Configuration/Event/EventSerializationAndDeserializationTest.php
