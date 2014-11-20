Step-up Middleware
==================

[![Build Status](https://travis-ci.org/SURFnet/Stepup-Middleware.svg)](https://travis-ci.org/SURFnet/Stepup-Middleware) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SURFnet/Stepup-Middleware/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/SURFnet/Stepup-Middleware/?branch=develop) [![SensioLabs Insight](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a/mini.png)](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a)

## Requirements

 * PHP 5.4+
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 5.5+ (MySQL should work as well)
 * Graylog2 (or disable this Monolog handler)
 * A working [Gateway](https://github.com/SURFnet/Stepup-Gateway)

## Installation

Clone the repository or download the archive to a directory. Install the dependencies by running `composer install` and fill out the database credentials et cetera.

## Notes

### Mocking Broadway DateTime::now()

To help with mocking time, the helper `BroadwayFixedDateTimeNow` was created. Call `::enable(DateTime)` to set a fixed
date/time, and call `::disable()` to… disable it. It is recommended to run a tests in a separate process (see
`IdentityCommandHandlerTest::testAYubikeyPossessionCanBeProven()`) when using this helper so the mock doesn't persist
between tests.

```php
/** @runTestInSeparateProcess */
public function testItWorks()
{
    # Trick `DateTime::now()` into thinking it is 1970.
    BroadwayFixedDateTimeNow::enable(new \DateTime('@0'));

    $this->assertEquals('1970-01-01T00:00:00.000000+00:00', \Broadway\Domain\DateTime::now()->toString());
}
```
