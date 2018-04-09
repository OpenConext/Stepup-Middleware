Step-up Middleware
==================

[![Build Status](https://travis-ci.org/OpenConext/Stepup-Middleware.svg)](https://travis-ci.org/OpenConext/Stepup-Middleware) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/OpenConext/Stepup-Middleware/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/OpenConext/Stepup-Middleware/?branch=develop) [![SensioLabs Insight](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a/mini.png)](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a)

This component is part of "Step-up Authentication as-a Service". See [Stepup-Deploy](https://github.com/OpenConext/Stepup-Deploy) for an overview and installation instructions for a complete Stepup system, including this component. The requirements and installation instructions below cover this component only.

## Requirements

 * PHP 5.6+ or PHP7
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 10
 * A working [Gateway](https://github.com/OpenConext/Stepup-Gateway)

## Installation

Clone the repository or download the archive to a directory. Install the dependencies by running `composer install` and fill out the database credentials et cetera.

Make sure to run database migrations using `app/console middleware:migrations:migrate`. 

## Management API

Some of the configuratio of the components is static (i.e. stored in parameteres.yml). The configuration that is expected to change during the operation of a Stepup system is managed through an API on the middleware. This provides  one place and action to change the configuration and allows changing of this configuration without having to modify the configuration of several components on several servers.

- The API calls are documented in the [middleware API documentation](./docs/MiddlewareManagementAPI.ml).
- The configuration itself is elaborate and is described in detail in the [Middlware configuration](./docs/MiddlewareConfiguration.md).
- The andible Stepup-Middleware role write scripts in /opt/stepup/  for pushing the configuration to the middleware component

## Development Notes

### Adding new events

Whenever adding a new event, be sure to update `app/config/events.yml`.
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

[event-serialization-example]: ./src/Surfnet/Stepup/Tests/Configuration/Event/EventSerializationAndDeserializationTest.php
