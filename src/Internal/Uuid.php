<?php

namespace Ulid\Internal;

if (class_exists('\Ramsey\Uuid\Uuid')) {
    /**
     * a wrapper class for \Ramsey\Uuid\Uuid to convert ulid to Uuid instance
     *
     * @internal
     */
    final class Uuid extends \Ramsey\Uuid\Uuid {}
} else {
    /**
     * a fallback class for returning a plain string
     *
     * @internal
     */
    final class Uuid {
        public static function fromBytes(string $bytes)
        {
            $combined = bin2hex($bytes);
            return implode('-', [
                substr($combined, 0, 8),
                substr($combined, 8, 4),
                substr($combined, 12, 4),
                substr($combined, 16, 4),
                substr($combined, 20, 12),
            ]);
        }
    }
}
