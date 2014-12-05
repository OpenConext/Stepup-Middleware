Step-up Middleware
==================

[![Build Status](https://travis-ci.org/SURFnet/Stepup-Middleware.svg)](https://travis-ci.org/SURFnet/Stepup-Middleware) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SURFnet/Stepup-Middleware/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/SURFnet/Stepup-Middleware/?branch=develop) [![SensioLabs Insight](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a/mini.png)](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a)

## Requirements

 * PHP 5.4+
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 10
 * Graylog2 (or disable this Monolog handler)
 * A working [Gateway](https://github.com/SURFnet/Stepup-Gateway)

## Installation

Clone the repository or download the archive to a directory. Install the dependencies by running `composer install` and fill out the database credentials et cetera.

## Management API

### Configuration API

Example cURL usage:
```
curl -XPOST -v \
    -u username:password \
    -H "Accept: application/json" \
    -H "Content-type: application/json" \
    -d @new_configuration.json \
    http://middleware.tld/management/configuration
```

### Configuration Structure

```json
{
  "raa": {
    "Example Inc": ["3858f62230ac3c915f300c664312c63f"]
  },
  "gateway": {
    "service_provider": [
      {
        "entity_id": "https://example.serviceprovider.tld/authentication/metadata",
        "public_key": "the public key contents (certificate data only)",
        "acs": [
          "https://example.serviceprovider.tld/authentication/consume-assertion"
        ],
        "loa": {
          "__default__": "https://example.gateway.tld/authentication/loa2"
        }
      }
    ]
  }
}
```

## Notes

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
