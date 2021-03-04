<?php

namespace Tests\Unit;

use Eve\DTO\DataTransferObjectException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Foo;
use Tests\Fixtures\NestedData;
use Tests\Fixtures\SampleData;

class DataTransferObjectTypeTest extends TestCase
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

    /** @return array<mixed> */
    public function provideMixedTypedProperty(): array
    {
        return [
            [null],
            ['foo'],
            [true],
            [new Foo()],
        ];
    }

    /** @dataProvider provideMixedTypedProperty */
    public function testMixedPropertyCanBeAnything($value): void
    {
        $data = SampleData::make();
        $data->mixed_prop = $value;

        self::assertEquals(['mixed_prop' => $value], $data->toArray());
    }

    /** @return array<mixed> */
    public function provideAliasedTypedProperty(): array
    {
        return [
            'int/integer' => ['int', 10],
            'bool/boolean' => ['bool', true],
            'float/double' => ['float', 10.0],
        ];
    }

    /** @dataProvider provideAliasedTypedProperty */
    public function testAliasTypedProperty(string $type, $value): void
    {
        $propName = "alias_prop_$type";
        $data = SampleData::make();
        $data->{$propName} = $value;

        self::assertEquals([$propName => $value], $data->toArray());
    }

    /** @return array<mixed> */
    public function provideUnionTypedProperty(): array
    {
        return [
            'string' => ['foo'],
            'Foo object' => [new Foo()],
        ];
    }

    /** @dataProvider provideUnionTypedProperty */
    public function testUnionProperty($value): void
    {
        $data = SampleData::make();
        $data->union_prop = $value;

        self::assertEquals(['union_prop' => $value], $data->toArray());
    }

    public function testNativeNullablePropertyAcceptsNull(): void
    {
        $data = SampleData::make();
        $data->nullable_prop = null;

        self::assertEquals(['nullable_prop' => null], $data->toArray());
    }

    public function testSettingNonExistentPropertyThrows(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Public property $nope does not exist in class Tests\Fixtures\SampleData');

        SampleData::make(['nope' => 'bar']);
    }

    /** @return array<mixed> */
    public function provideInvalidType(): array
    {
        return [
            [null, 'NULL'],
            [1, 'integer'],
            [true, 'boolean'],
            [1.0, 'double'],
            [new Foo(), Foo::class],
        ];
    }

    /** @dataProvider provideInvalidType */
    public function testSettingInvalidTypeThrows($value, string $type): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage(
            sprintf(
                'Tests\Fixtures\SampleData::$simple_prop must be of type string, received a value of type %s.',
                $type
            )
        );

        SampleData::make(['simple_prop' => $value]);
    }

    public function testSettingInvalidTypeOnUnionTypeThrows(): void
    {
        self::expectException(DataTransferObjectException::class);
        self::expectExceptionMessage('Tests\Fixtures\SampleData::$union_prop must be one of these types: string, \Tests\Fixtures\Foo; received a value of type boolean.'); //@phpcs-ignore

        SampleData::make(['union_prop' => false]);
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
}
