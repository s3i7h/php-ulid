<?php

namespace Ulid;

use Exception;
use JsonSerializable;
use Stringable;
use Ulid\Internal\Base32;
use Ulid\Internal\ByteArray;

class Ulid implements JsonSerializable, Stringable
{
    const REGEX_ULID_REPRESENTATION = '/[0-7][0123456789ABCDEFGHJKMNPQRSTVWXYZ]{25}/';
    const REGEX_UUID_REPRESENTATION = '/[0-F]{8}-[0-F]{4}-[0-F]{4}-[0-F]{4}-[0-F]{12}/';

    /** @var ByteArray $bytes */
    protected $bytes;

    /**
     * @param Ulid|int|string|array|null $value
     * @throws Exception
     */
    public function __construct($value = null)
    {
        if ($value instanceof Ulid) {
            $self = clone $value;
            $this->bytes = $self->bytes;
        } else if ($value instanceof ByteArray) {
            $this->bytes = $value->convertBits(8, 16);
        } else if (is_int($value)) {
            $self = static::generateFromTimestamp($value);
            $this->bytes = $self->bytes;
        } else if (is_string($value)) {
            if (preg_match(static::REGEX_ULID_REPRESENTATION, strtoupper($value))) {
                $self = static::parseUlidString(strtoupper($value));
            } else if (preg_match(static::REGEX_UUID_REPRESENTATION, strtoupper($value))) {
                $self = static::parseUuidString(strtoupper($value));
            } else {
                $self = new static(ByteArray::fromBytes($value));
            }
            $this->bytes = $self->bytes;
        } else {
            $self = static::generate();
            $this->bytes = $self->bytes;
        }
    }

    /**
     * @return static
     * @throws Exception
     */
    public static function generate()
    {
        return static::generateFromTimestamp(floor(microtime(true) * 1000));
    }

    /**
     * @param int $timestamp timestamp in milliseconds
     * @return static
     * @throws Exception
     */
    public static function generateFromTimestamp(int $timestamp)
    {
        $ts = ByteArray::fromInt($timestamp)->chomp(6);
        $random = ByteArray::fromBytes(random_bytes(10));

        return new static(new ByteArray(array_merge($ts->toArray(), $random->toArray())));
    }

    /**
     * @param string $value
     * @return static
     * @throws Exception
     */
    public static function parseUlidString(string $value)
    {
        return new static(Base32::decode($value));
    }

    /**
     * @param string $value
     * @return static
     * @throws Exception
     */
    public static function parseUuidString(string $value)
    {
        return new static(hex2bin(str_replace('-', '', $value)));
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return Base32::encode($this->bytes->convertBits(5, 26));
    }

    /**
     * @return string
     */
    public function toBytes(): string
    {
        return $this->bytes->toBytes();
    }

    /**
     * @return string
     */
    public function toUuid(): string
    {
        $combined = bin2hex($this->bytes->toBytes());
        return implode('-', [
            substr($combined, 0, 8),
            substr($combined, 8, 4),
            substr($combined, 12, 4),
            substr($combined, 16, 4),
            substr($combined, 20, 12),
        ]);
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}