<?php

namespace Tests\Unit;

use Eve\DTO\DataTransferObjectException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Foo;
use Tests\Fixtures\NestedData;
use Tests\Fixtures\SampleData;
use Tests\Fixtures\UntypedData;

class DataTransferObjectTest extends TestCase
{
    public function testSimpleProperty(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = 'bar';

        self::assertEquals([
            'initialized_prop' => 'Initialized',
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());
    }

    public function testInitializedProperty(): void
    {
        self::assertEquals(['initialized_prop' => 'Initialized'], SampleData::make()->toArray());
    }

    public function testArrayProperty(): void
    {
        $data = SampleData::make();
        $data->array_prop = ['foo' => 'bar'];

        self::assertEquals([
            'initialized_prop' => 'Initialized',
            'array_prop' => ['foo' => 'bar'],
        ], $data->toArray());
    }

    public function testObjectProperty(): void
    {
        $foo = new Foo();
        $data = SampleData::make();
        $data->object_prop = $foo;

        self::assertEquals([
            'initialized_prop' => 'Initialized',
            'object_prop' => $foo,
        ], $data->toArray());
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

        self::assertSame([
            'initialized_prop' => 'Initialized',
            'simple_prop' => 'foo',
        ], $data->toArray());
    }

    public function testSetArray(): void
    {
        $data = SampleData::make();
        $data->set([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ]);

        self::assertSame([
            'initialized_prop' => 'Initialized',
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
            'initialized_prop' => 'Initialized',
        ], $data->toArray());

        unset($data->nullable_prop);

        self::assertEquals([
            'simple_prop' => 'foo',
            'initialized_prop' => 'Initialized',
        ], $data->toArray());
    }

    public function testUnset(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = 'bar';

        self::assertEquals([
            'initialized_prop' => 'Initialized',
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());

        $data->unset('nullable_prop');

        self::assertEquals([
            'simple_prop' => 'foo',
            'initialized_prop' => 'Initialized',
        ], $data->toArray());
    }

    public function testUnsetSpread(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = 'bar';

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
            'initialized_prop' => 'Initialized',
        ], $data->toArray());

        $data->unset('nullable_prop', 'simple_prop', 'initialized_prop');

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
            'initialized_prop' => 'Initialized',
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->toArray());
    }

    public function testCompact(): void
    {
        $data = SampleData::make();
        $data->simple_prop = 'foo';
        $data->nullable_prop = null;

        self::assertEquals([
            'initialized_prop' => 'Initialized',
            'simple_prop' => 'foo',
        ], $data->compact()->toArray());
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
        ]);

        self::assertEquals([
            'simple_prop' => 'foo',
            'initialized_prop' => 'Initialized',
        ], $data->only('simple_prop', 'initialized_prop')->toArray());
    }

    public function testOnlyThrowsIfPropertyDoesNotExist(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Public property $nope does not exist in class Tests\Fixtures\SampleData');

        SampleData::make()->only('simple_prop', 'nope')->toArray();
    }

    public function testExcept(): void
    {
        $data = SampleData::make([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ]);

        self::assertEquals([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ], $data->except('initialized_prop')->toArray());
    }

    public function testExceptSpread(): void
    {
        $data = SampleData::make([
            'simple_prop' => 'foo',
            'nullable_prop' => 'bar',
        ]);

        self::assertEquals(
            ['initialized_prop' => 'Initialized'],
            $data->except('simple_prop', 'nullable_prop')->toArray()
        );
    }

    public function testExceptThrowsIfPropertyDoesNotExist(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Public property $nope does not exist in class Tests\Fixtures\SampleData');

        SampleData::make()->except('nope')->toArray();
    }

    public function testNestedDTO(): void
    {
        $data = SampleData::make();
        $data->nested = NestedData::make(['sample_prop' => 'sample']);

        self::assertEquals([
            'nested' => ['sample_prop' => 'sample'],
            'initialized_prop' => 'Initialized',
        ], $data->compact()->toArray());
    }

    public function testPropertyAccessViaGet(): void
    {
        $data = SampleData::make(['simple_prop' => 'foo']);

        self::assertSame('foo', $data->get('simple_prop'));
    }

    public function testPropertyAccessViaGetWithDefault(): void
    {
        self::assertSame('foo', SampleData::make()->get('simple_prop', 'foo'));
    }

    public function testAccessingNonExistentPropertyWillThrow(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Public property $nope does not exist in class Tests\Fixtures\SampleData');

        echo SampleData::make()->nope;
    }

    public function testDirectPropertyAccess(): void
    {
        $data = SampleData::make(['simple_prop' => 'foo']);

        self::assertSame('foo', $data->simple_prop);
    }

    public function testAccessingNonInitializedAccessThrows(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage(
            'Tests\Fixtures\SampleData::$simple_prop must not be accessed before initialization.'
        );

        echo SampleData::make()->simple_prop;
    }

    public function testUntypedData(): void
    {
        $data = UntypedData::make();

        self::assertSame([
            'foo_prop' => 'Foo',
            'null_prop' => null,
        ], $data->toArray());

        $data = UntypedData::make(['null_prop' => 'Not so null']);

        self::assertSame([
            'foo_prop' => 'Foo',
            'null_prop' => 'Not so null',
        ], $data->toArray());
    }
}
