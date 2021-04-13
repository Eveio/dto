<?php

namespace Tests\Unit;

use Eve\DTO\DataTransferObjectException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Foo;
use Tests\Fixtures\NestedData;
use Tests\Fixtures\SampleData;

class DataTransferObjectTest extends TestCase
{
    public function testSimpleProperty(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = 'bar';

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());
    }

    public function testArrayProperty(): void
    {
        $data = SampleData::make();
        $data->array_prop = ['foo' => 'bar'];

        self::assertEquals(['array_prop' => ['foo' => 'bar']], $data->toArray());
    }

    public function testObjectProperty(): void
    {
        $foo = new Foo();
        $data = SampleData::make();
        $data->object_prop = $foo;

        self::assertEquals(['object_prop' => $foo], $data->toArray());
    }

    public function testSettingNonExistentPropertyThrows(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Public property $nope does not exist in class Tests\Fixtures\SampleData');

        SampleData::make(['nope' => 'bar']);
    }

    public function testSet(): void
    {
        $data = SampleData::make();
        $data->set('simple_prop', 'foo');

        self::assertSame(['simple_prop' => 'foo'], $data->toArray());
    }

    public function testSetArray(): void
    {
        $data = SampleData::make();
        $data->set([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ]);

        self::assertSame([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());
    }

    public function testBuiltinUnset(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = 'bar';

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());

        unset($data->nullable_prop);

        self::assertEquals(['simple_prop' => 'foo'], $data->toArray());
    }

    public function testUnset(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = 'bar';

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());

        $data->unset('nullable_prop');

        self::assertEquals(['simple_prop' => 'foo'], $data->toArray());
    }

    public function testUnsetSpread(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = 'bar';

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());

        $data->unset('nullable_prop', 'simple_prop');

        self::assertEquals([], $data->toArray());
    }

    public function testUnsettingNonExistentPropertyThrows(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Public property $nope does not exist in class Tests\Fixtures\SampleData');

        SampleData::make()->unset('nope');
    }

    public function testMake(): void
    {
        $data = SampleData::make([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ]);

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());
    }

    public function testCompact(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = null;

        self::assertEquals(['simple_prop' => 'foo'], $data->compact()->toArray());
    }

    public function testOnly(): void
    {
        $data = SampleData::make([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ]);

        self::assertEquals(['simple_prop' => 'foo'], $data->only('simple_prop')->toArray());
    }

    public function testOnlySpread(): void
    {
        $data = SampleData::make([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
            'mixed_prop' => 'baz',
        ]);

        self::assertEquals([
            'simple_prop' => 'foo',
            'mixed_prop' => 'baz',
        ], $data->only('simple_prop', 'mixed_prop')->toArray());
    }

    public function testExcept(): void
    {
        $data = SampleData::make([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
            'mixed_prop' => 'baz',
        ]);

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->except('mixed_prop')->toArray());
    }

    public function testExceptSpread(): void
    {
        $data = SampleData::make([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
            'mixed_prop' => 'baz',
        ]);

        self::assertEquals(['simple_prop' => 'foo'], $data->except('mixed_prop', 'nullable_prop')->toArray());
    }

    public function testNestedDTO(): void
    {
        $data = SampleData::make();
        $data->nested = NestedData::make(['sample_prop' => 'sample']);

        self::assertEquals(['nested' => ['sample_prop' => 'sample']], $data->compact()->toArray());
    }

    public function testPropertyAccess(): void
    {
        $data = SampleData::make(['simple_prop' => 'foo']);
        
        self::assertSame('foo', $data->get('simple_prop'));
        self::assertSame('foo', $data->simple_prop);
    }

    public function testAccessingNonExistentPropertyWillThrow(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Public property $nope does not exist in class Tests\Fixtures\SampleData');

        echo SampleData::make()->nope;
    }
}
