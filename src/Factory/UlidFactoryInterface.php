<?php

namespace Ulid\Factory;

use Ulid\Ulid;

interface UlidFactoryInterface
{
    public static function generate(): Ulid;

    public static function generateFromTimestamp(int $timestamp): Ulid;
}
