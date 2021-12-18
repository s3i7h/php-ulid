<?php

namespace Ulid\Internal;

/**
 * A class for converting between ByteArray and base32 string
 *
 * @internal
 */
class Base32
{
    const BASE_32_CHARS = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    const BASE_32_TABLE = [
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 10,
        'B' => 11,
        'C' => 12,
        'D' => 13,
        'E' => 14,
        'F' => 15,
        'G' => 16,
        'H' => 17,
        'J' => 18,
        'K' => 19,
        'M' => 20,
        'N' => 21,
        'P' => 22,
        'Q' => 23,
        'R' => 24,
        'S' => 25,
        'T' => 26,
        'V' => 27,
        'W' => 28,
        'X' => 29,
        'Y' => 30,
        'Z' => 31,
    ];

    public static function decode(string $value): ByteArray
    {
        assert(preg_match('/^[' . static::BASE_32_CHARS .']+$/', $value), '$value must be a valid base32 string');
        $decoded = [];
        foreach (str_split($value) as $char) {
            $decoded[] = static::BASE_32_TABLE[$char];
        }
        return new ByteArray($decoded, 5);
    }

    public static function encode(ByteArray $value): string
    {
        $encoded = '';
        foreach ($value->convertBits(5)->toArray() as $segment) {
            $encoded .= static::BASE_32_CHARS[$segment];
        }
        return $encoded;
    }
}
