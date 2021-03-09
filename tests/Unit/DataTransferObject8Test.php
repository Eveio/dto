<?php

namespace Tests\Unit;

use Eve\DTO\DataTransferObjectException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\SampleData8;

class DataTransferObject8Test extends TestCase
{
    public function testPhp8Support(): void
    {
        if (PHP_VERSION_ID < 80000) {
            self::markTestSkipped();
        }

        $data = SampleData8::make(['compound_property' => 'Bob']);
        self::assertSame(['compound_property' => 'Bob'], $data->toArray());

        $data = SampleData8::make(['compound_property' => 10]);
        self::assertSame(['compound_property' => 10], $data->toArray());

        self::expectException(DataTransferObjectException::class);
        SampleData8::make(['compound_property' => false]);
    }
}
