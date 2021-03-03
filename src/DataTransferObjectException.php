<?php

namespace Eve\DTO;

use Exception;
use ReflectionProperty;
use Throwable;

class DataTransferObjectException extends Exception
{
    private function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function invalidType(ReflectionProperty $targetProperty, string $type, array $allowedTypes): self
    {
        if (count($allowedTypes) === 1) {
            return new static(
                sprintf(
                    '%s::$%s must be of type %s, received a value of type %s.',
                    $targetProperty->class,
                    $targetProperty->name,
                    $allowedTypes[0],
                    $type
                )
            );
        }

        return new static(
            sprintf(
                '%s::$%s must be one of these types: %s; received a value of type %s.',
                $targetProperty->class,
                $targetProperty->name,
                implode(', ', $allowedTypes),
                $type
            )
        );
    }

    public static function nonexistentProperty(string $class, string $propertyName): self
    {
        return new static(sprintf('Public property $%s does not exist in class %s.', $propertyName, $class));
    }
}
