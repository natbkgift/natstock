<?php

namespace Carbon;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/**
 * Minimal replacement for Carbon needed in the simplified environment.
 */
class Carbon extends DateTimeImmutable
{
    /**
     * Create a Carbon instance for today (start of day in default timezone).
     */
    public static function today(): self
    {
        return new self('today midnight');
    }

    /**
     * Create a Carbon instance from a date string or DateTimeInterface.
     *
     * @throws Exception
     */
    public static function parse(DateTimeInterface|string $time = 'now'): self
    {
        if ($time instanceof DateTimeInterface) {
            return new self($time->format(DateTimeInterface::ATOM));
        }

        return new self($time);
    }

    public static function instance(DateTimeInterface $dateTime): self
    {
        return new self($dateTime->format(DateTimeInterface::ATOM));
    }

    public function copy(): self
    {
        return new self($this->format(DateTimeInterface::ATOM));
    }

    public function addDays(int $days): self
    {
        return $this->modify(sprintf('+%d days', $days));
    }

    public function toDateString(): string
    {
        return $this->format('Y-m-d');
    }

    public function between(self $start, self $end): bool
    {
        return $this >= $start && $this <= $end;
    }
}
