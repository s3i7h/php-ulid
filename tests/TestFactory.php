<?php

use Ulid\Factory\MonotonicFactory;

class TestFactory extends MonotonicFactory
{
    public static $lastGeneratedBytes = null;
    /** @var callable(): int */
    public static $timestamp = [MonotonicFactory::class, 'timestamp'];
    /** @var callable(): string */
    public static $randomness = [MonotonicFactory::class, 'randomness'];

    public static function timestamp(): int
    {
        return (static::$timestamp)();
    }

    public static function randomness(): string
    {
        return (static::$randomness)();
    }
}