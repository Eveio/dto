<?php

namespace Tests\Fixtures;

use Eve\DTO\DataTransferObject;

class UntypedData extends DataTransferObject
{
    public $foo_prop = 'Foo';
    public $null_prop;
}
