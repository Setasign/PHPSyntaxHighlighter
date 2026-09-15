<?php

declare(strict_types=1);

namespace Foo\Bar;

use DateInterval;
use DateTimeInterface;
use DateTimeZone;

$test = new \DateTime();
class Test implements \DateTimeInterface {
    private DateTimeInterface $test;

    public function __construct(private DateTimeInterface $dateTime) {
    }

    public function diff(DateTimeInterface $targetObject, bool $absolute = false): DateInterval
    {
        return $this->dateTime->diff($targetObject, $absolute);
    }

    public function format(string $format): string
    {
        return $this->dateTime->format($format);
    }

    public function getOffset(): int
    {
        return $this->dateTime->getOffset();
    }

    public function getTimestamp()
    {
        return $this->dateTime->getTimestamp();
    }

    public function getTimezone(): DateTimeZone|false
    {
        return $this->dateTime->getTimezone();
    }

    public function __wakeup(): void
    {
    }

    public function __serialize(): array
    {
        throw new \BadMethodCallException();
    }

    public function __unserialize(array $data): void
    {
    }

    public function getMicrosecond(): int
    {
        return $this->dateTime->getMicrosecond();
    }
}