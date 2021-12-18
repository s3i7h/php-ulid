<?php

namespace Ulid\Internal;

if (interface_exists('\JsonSerializable')) {
    /**
     * a wrapper interface for \JsonSerializable
     *
     * @internal
     */
    interface JsonSerializable extends \JsonSerializable
    {
    }
} else {
    /**
     * a fallback interface for JsonSerializable
     *
     * @internal
     */
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
    interface JsonSerializable
    {
        /**
         * @return mixed
         */
        public function jsonSerialize();
    }
}
