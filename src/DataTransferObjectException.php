<?php

namespace Eve\DTO;

use Exception;
use Throwable;

class DataTransferObjectException extends Exception
{
    private function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function nonexistentProperty(string $class, string $propertyName): self
    {
        return new static(sprintf('Public property $%s does not exist in class %s.', $propertyName, $class));
    }

    public static function propertyNotInitialized(string $class, string $propertyName)
    {
        return new static(sprintf('%s::$%s must not be accessed before initialization.', $class, $propertyName));
    }
}
