<?php

namespace Ulid\Internal;

use OverflowException;

/**
 * A class to manipulate byte array (and an array like it)
 * Stores internally in little endian, but represents it in big endian
 *
 * example:
 * $a = new ByteArray([1, 0]);
 * $b = new ByteArray([1]);
 * $a->add($b)->toBytes() === (new ByteArray([1, 1]))->toBytes();
 *
 * @internal
 */
class ByteArray
{
    /** @var int $bits */
    protected $bits;

    /** @var int[] $arrays */
    protected $values;

    /**
     * @param int[] $values
     * @param int|null $bits
     */
    public function __construct(array $values, int $bits = 8)
    {
        $maxBits = self::maxBits();
        assert(1 <= $bits && $bits <= $maxBits, "bits must be between 1 and $maxBits (inclusive)");
        $this->bits = $bits;
        $this->values = array_reverse($values);
        foreach ($this->values as $value) {
            assert(is_int($value));
            assert($value <= self::bitmask($this->bits));
        }
    }

    /**
     * calculate maximum bits allowed for a single int
     *
     * @return int
     */
    private static function maxBits()
    {
        return PHP_INT_SIZE * 8 - 1;
    }

    /**
     * overflow safe bitmask generator
     * self::bitmask(3) === 7 === 0b111
     * self::bitmask(self::maxBits()) === PHP_INT_MAX
     *
     * @param int $bits
     * @return int
     */
    private static function bitmask(int $bits)
    {
        return PHP_INT_MAX >> self::maxBits() - $bits;
    }

    /**
     * @param string $value
     * @return static
     */
    public static function fromBytes(string $value)
    {
        return new static(array_values(unpack('C*', $value)));
    }

    /**
     * @param int $value
     * @return static
     */
    public static function fromInt(int $value)
    {
        return new static([$value], self::maxBits());
    }

    /**
     * @param self $other
     * @return static
     */
    public function concat(self $other)
    {
        return new static(array_merge(
            $this->toArray(),
            $other->convertBits($this->bits)->trim()->toArray()
        ), $this->bits);
    }

    /**
     * @param int|array|self $target
     * @return static
     */
    public function add($target)
    {
        if (is_int($target)) {
            return $this->add(static::fromInt($target)->convertBits($this->bits));
        } else if (is_array($target)) {
            return $this->add(new static($target));
        }
        assert($target instanceof self);
        assert($this->bits === $target->bits);

        $target = $target->trim();
        $result = [];
        $carry = 0;
        $i = 0;
        if (count($target->values) > count($this->values)) {
            throw new OverflowException();
        }
        while ($i < count($this->values)) {
            $other = $i < count($target->values) ? $target->values[$i] : 0;
            $value = $this->values[$i];
            $sum = 0;
            if ($other > self::bitmask($this->bits) - $value - $carry) {
                $sum += -self::bitmask($this->bits) - 1 + $carry;
                $carry = 1;
            } else {
                $sum += $carry;
                $carry = 0;
            }
            $sum += $other;
            $sum += $value;
            $result[] = $sum;
            $i++;
        }
        if ($carry) {
            throw new OverflowException();
        }
        return new static(array_reverse($result), $this->bits);
    }

    /**
     * e.g.
     * (new ByteArray([254, 254]))->convertBits(4)->toArray() === [15, 14, 15, 14]
     *
     * @param int $bits
     * @param int $length
     * @return static
     */
    public function convertBits(int $bits, int $length = null)
    {
        $result = [];
        $i = 0;

        $carry = 0;
        $carriedBits = 0;

        while ($length ? count($result) < $length : ($carriedBits || $i < count($this->values))) {
            $valueAccessible = $i < count($this->values);
            $value = $valueAccessible ? $this->values[$i] : 0;
            $bitDiff = $carriedBits + $this->bits - $bits;
            if ($valueAccessible && $bitDiff < 0) {
                // carry won't be greater than PHP_INT_MAX
                $carry |= $value << $carriedBits;
                $i++;
                $carriedBits += $this->bits;
                continue;
            }
            $item = $carry & self::bitmask($bits);
            $itemBits = min($carriedBits, $bits);
            $carry >>= $itemBits;
            $carriedBits -= $itemBits;
            $maskBits = $bits - $itemBits;

            if ($valueAccessible && $maskBits > 0) {
                $item |= ($value & self::bitmask($maskBits)) << $itemBits;
                $carry |= $value >> $maskBits;
                $i++;
                $carriedBits += $this->bits - $maskBits;
            }
            $result[] = $item;
        }
        return new static(array_reverse($result), $bits);
    }

    /**
     * slice the byte array from the lower end
     *
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function chomp(int $offset = null, int $length = null)
    {
        if (is_null($offset) && is_null($length)) {
            $offset = count($this->values);
        } else if (is_null($offset)) {
            $offset = 0;
        }
        if (is_null($length)) {
            $length = $offset;
            $offset = 0;
        }
        return new static(array_reverse(array_slice($this->values, $offset, $length)), $this->bits);
    }

    /**
     * slice the byte array from the higher end
     *
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice(int $offset = null, int $length = null)
    {
        if (is_null($offset) && is_null($length)) {
            $offset = count($this->values);
        } else if (is_null($offset)) {
            $offset = 0;
        }
        if (is_null($length)) {
            $length = $offset;
            $offset = 0;
        }
        return new static(array_slice(array_reverse($this->values), $offset, $length), $this->bits);
    }

    /**
     * Drop the higher 0s
     *
     * @return static
     */
    public function trim()
    {
        $trimmed = [];
        foreach ($this->toArray() as $item) {
            if (! $item) {
                continue;
            }
            $trimmed[] = $item;
        }
        return new static($trimmed, $this->bits);
    }

    public function toBytes(): string
    {
        return pack("C*", ...array_reverse($this->convertBits(8)->values));
    }

    public function toArray(): array
    {
        return array_reverse($this->values);
    }
}