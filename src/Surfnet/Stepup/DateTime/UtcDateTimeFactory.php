<?php

namespace Surfnet\Stepup\DateTime;

use DateTime as CoreDateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class UtcDateTimeFactory
{
    /**
     * Allows for mocking of time.
     *
     * @var UtcDateTime|null
     */
    private static $now;

    /**
     * @var LoggerInterface|null
     */
    public static $logger;

    /**
     * @return UtcDateTime
     */
    public static function now()
    {
        return self::$now ?: new UtcDateTime(new CoreDateTime('now', new DateTimeZone('UTC')));
    }

    /**
     * @param string $string A date-time string formatted using `self::FORMAT` (eg. '2014-11-26T15:20:43+01:00').
     * @return UtcDateTime
     */
    public static function fromString($string)
    {
        if (!is_string($string)) {
            InvalidArgumentException::invalidType('string', 'dateTime', $string);
        }

        $dateTime = CoreDateTime::createFromFormat(UtcDateTime::FORMAT, $string);

        if ($dateTime === false) {
            throw new InvalidArgumentException('Date-time string could not be parsed: is it formatted correctly?');
        }

        return static::createFromTimezonedUtcDateTime($dateTime);
    }

    /**
     * @deprecated UTC datetimes should be enforced on input.
     * @param CoreDateTime $dateTime
     * @return UtcDateTime
     */
    public static function createFromTimezonedUtcDateTime(CoreDateTime $dateTime)
    {
        if ($dateTime->getOffset() === 0) {
            return new UtcDateTime($dateTime);
        }

        $incorrectOffset = $dateTime->getOffset();
        $dateTime->setTimeZone(new DateTimeZone('UTC'));

        if (!static::$logger) {
            return new UtcDateTime($dateTime);
        }

        static::$logger->warning(sprintf('Creating Stepup DateTime, expected no timezone offset, but got offset "%s"', $incorrectOffset));

        return new UtcDateTime($dateTime);
    }
}
