<?php

namespace Ulid\Internal;

use OverflowException;

/**
 * A class to manipulate array as byte array in little endian
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
        $this->bits = $bits;
        $this->values = array_reverse($values);
        foreach ($this->values as $value) {
            assert(is_int($value));
            assert($value < 2**$this->bits);
        }
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
        return static::fromBytes(pack("J", $value));
    }

    public function isZero(): bool
    {
        return array_reduce($this->values, function($acc, $item) {
            if (! $acc) return $acc;
            return $item === 0;
        }, true);
    }

    /**
     * @param int|array|self $target
     * @return static
     */
    public function add($target)
    {
        if (is_int($target)) {
            return $this->add(static::fromInt($target));
        } else if (is_array($target)) {
            return $this->add(new static($target));
        }
        assert($target instanceof self);
        assert($this->bits === $target->bits);

        $result = [];
        $carry = 0;
        $i = 0;
        if (count($target->values) > count($this->values)) {
            throw new OverflowException();
        }
        while ($i < count($this->values)) {
            $sum = $i > count($target->values) ? 0 : $target->values[$i];
            $sum += $carry + $this->values[$i];
            $carry = $sum >> $this->bits;
            $result[] = $sum & (2**$this->bits-1);
            $i++;
        }
        if ($carry) {
            throw new OverflowException();
        }
        return new static(array_reverse($result), $this->bits);
    }

    /**
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

        while(count($result) < ($length ?: PHP_INT_MAX) && $i < count($this->values)) {
            $bitDiff = $carriedBits + $this->bits - $bits;
            if ($bitDiff < 0) {
                $carry <<= $this->bits;
                $carry += $this->values[$i];
                $carriedBits += $this->bits;
            } else {
                $maskBits = $bits - $carriedBits;
                if ($maskBits < 0) {
                    $result[] = $carry & (2** $bits -1);
                    $carry >>= $bits;
                    $carriedBits -= $bits;
                    continue;
                } else {
                    $item = $this->values[$i] & (2**$maskBits-1);
                    $item <<= $carriedBits;
                    $item += $carry;
                    $result[] = $item;
                    $carry = $this->values[$i] >> $maskBits;
                    $carriedBits = $this->bits - $maskBits;
                }
            }
            $i++;
        }
        while ($length ? count($result) < $length : $carry) {
            $result[] = $carry & (2** $bits -1);
            $carry >>= $bits;
        }
        return new static(array_reverse($result), $bits);
    }

    /**
     * @param int $length
     * @return static
     */
    public function chomp(int $length)
    {
        return new static(array_reverse(array_slice($this->values, 0, $length)), $this->bits);
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