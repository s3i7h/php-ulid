<?php

namespace Tests;

use Ulid\Ulid;

final class UlidTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        Ulid::$factory = TestFactory::class;
    }

    protected function setUp(): void
    {
        TestFactory::$lastGeneratedBytes = null;
    }

    public function testGenerate()
    {
        $this->assertMatchesRegularExpression(Ulid::REGEX_ULID_REPRESENTATION, (string) new Ulid);
    }

    public function testTimestampGenerate()
    {
        TestFactory::$timestamp = fn () => 32;
        TestFactory::$randomness = fn () => hex2bin('00000000000000000000');
        $this->assertSame('00000000000000000000000000', (string) new Ulid(0));
    }

    public function testMonotonicity()
    {
        $idx = 0;
        $timestamps = [0, 0, 1];
        $pseudoRandom = [
            hex2bin('00000000000000000000'),
            hex2bin('00000000000000414243'),
            hex2bin('000000000000000fffff'),
        ];
        TestFactory::$timestamp = function () use (&$idx, $timestamps) {
            return $timestamps[$idx];
        };
        TestFactory::$randomness = function () use (&$idx, $pseudoRandom) {
            return $pseudoRandom[$idx];
        };
        $this->assertSame('00000000000000000000000000', (string) new Ulid);
        $idx++;
        $this->assertSame('00000000000000000000000001', (string) new Ulid);
        $idx++;
        $this->assertSame('0000000001000000000000ZZZZ', (string) new Ulid);
    }

    public function testOverflow()
    {
        TestFactory::$timestamp = fn () => 0xffffffffffff;
        TestFactory::$randomness = fn () => hex2bin('ffffffffffffffffffff');
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', (string) new Ulid);
        $this->expectException(OverflowException::class);
        new Ulid;
    }

    public function testUuid()
    {
        TestFactory::$timestamp = fn () => 0;
        TestFactory::$randomness = fn () => hex2bin('aaaabbbbcccccccccccc');
        $this->assertSame('00000000-0000-aaaa-bbbb-cccccccccccc', (string) (new Ulid)->toUuid());
    }

    public function testClone()
    {
        $ulid = new Ulid();
        $this->assertSame((string) $ulid, (string) new Ulid($ulid));
    }

    public function testParseUlid()
    {
        $ulid = '0000000001000000000000ZZZZ';
        $this->assertSame($ulid, (string) new Ulid($ulid));
    }

    public function testParseUuid()
    {
        $uuid = '00000000-0000-aaaa-bbbb-cccccccccccc';
        $this->assertSame($uuid, (string) (new Ulid($uuid))->toUuid());
    }

    public function testParseBytes()
    {
        $bytes = "\0\0\0\0\0\0          ";
        $ulid = new Ulid($bytes);
        $this->assertSame($bytes, (new Ulid($bytes))->toBytes());
        $this->assertSame('0000000000' . '40G20810' . '40G20810', (string) $ulid);
    }

    public function testInvalidValue()
    {
        $invalid = new stdClass();
        $this->expectError();
        new Ulid($invalid);
    }

    public function testJsonSerialize()
    {
        TestFactory::$timestamp = fn () => 0xffffffffffff;
        TestFactory::$randomness = fn () => hex2bin('ffffffffffffffffffff');
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', (new Ulid)->jsonSerialize());
    }

    public function testDebugInfo()
    {
        TestFactory::$timestamp = fn () => 0xffffffffffff;
        TestFactory::$randomness = fn () => hex2bin('ffffffffffffffffffff');
        $this->assertIsArray((new Ulid)->__debugInfo());
    }
}
