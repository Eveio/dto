<?php

namespace Tests\Fixtures;

use Eve\DTO\DataTransferObject;

class SampleData extends DataTransferObject
{
    public string $simple_prop;
    public ?string $nullable_prop;
    public $mixed_prop;
    public array $array_prop;
    public Foo $object_prop;
    public NestedData $nested;
}
