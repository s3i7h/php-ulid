<?php

namespace Ulid\Factory;

use Exception;
use Ulid\Internal\ByteArray;
use Ulid\Ulid;

class MonotonicFactory implements UlidFactoryInterface
{
    /** @var ByteArray|null */
    public static $lastGeneratedBytes = null;

    /**
     * @throws Exception
     */
    public static function randomness(): string
    {
        return random_bytes(10);
    }

    /**
     * @throws Exception
     */
    public static function generate(): Ulid
    {
        return self::generateFromTimestamp((int) floor(microtime(true) * 1000));
    }

    /**
     * @throws Exception
     */
    public static function generateFromTimestamp(int $timestamp): Ulid
    {
        $ts = ByteArray::fromInt($timestamp)->convertBits(8, 6);
        $random = (
            static::$lastGeneratedBytes
            && static::$lastGeneratedBytes->slice(6)->toBytes() === $ts->toBytes()
        )
            ? static::$lastGeneratedBytes->chomp(10)->add(1)
            : ByteArray::fromBytes(static::randomness());

        $next = $ts->concat($random);
        static::$lastGeneratedBytes = $next;
        return new Ulid($next);
    }
}