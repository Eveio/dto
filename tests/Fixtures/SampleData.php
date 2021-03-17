<?php

namespace Tests\Fixtures;

use Eve\DTO\DataTransferObject;

class SampleData extends DataTransferObject
{
    public string $simple_prop;
    public ?string $nullable_prop;
    public array $array_prop;
    public Foo $object_prop;

    public $mixed_prop;

    public int $alias_prop_int;
    public bool $alias_prop_bool;
    public float $alias_prop_float;

    /** @var Foo|string */
    public string $union_prop;

    /** @var string|null */
    public $nullable_doctype_prop;

    /** @var Boolean|Int|String */
    public $mixed_case_type_prop;

    public NestedData $nested;
}
