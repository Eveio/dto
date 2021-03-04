<?php

namespace Eve\DTO;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context;
use ReflectionProperty;

class TypeValidator
{
    private ReflectionProperty $property;
    private DocBlockFactory $docBlockFactory;
    private TypeResolver $typeResolver;
    private Context $context;
    private array $allowedTypes;

    private const TYPE_ALIASES = [
        'int' => 'integer',
        'bool' => 'boolean',
        'float' => 'double',
    ];

    public function __construct(
        DocBlockFactory $docBlockFactory,
        TypeResolver $typeResolver,
        Context $context,
        ReflectionProperty $property
    ) {
        $this->docBlockFactory = $docBlockFactory;
        $this->typeResolver = $typeResolver;
        $this->context = $context;
        $this->property = $property;

        $this->allowedTypes = array_unique(array_merge($this->getNativeTypes(), $this->getDockBlockTypes()));
    }

    public function validate($value): void
    {
        if (!$this->allowedTypes) {
            // an empty array of allowed types means all types are allowed
            return;
        }

        $actualType = gettype($value);

        foreach ($this->allowedTypes as $type) {
            if ($actualType === $type) {
                return;
            }

            if (array_key_exists($type, self::TYPE_ALIASES) && self::TYPE_ALIASES[$type] === $actualType) {
                return;
            }

            if ($value instanceof $type) {
                return;
            }
        }

        throw DataTransferObjectException::invalidType(
            $this->property,
            $actualType === 'object' ? get_class($value) : $actualType,
            $this->allowedTypes
        );
    }

    /** @return array<string> */
    private function getNativeTypes(): array
    {
        $nativeType = $this->property->getType();

        if ($nativeType) {
            return $nativeType->allowsNull() ? ['NULL', $nativeType->getName()] : [$nativeType->getName()];
        }

        return [];
    }

    /** @return array<string> */
    private function getDockBlockTypes(): array
    {
        if (!$this->property->getDocComment()) {
            return [];
        }

        $types = [];

        $docblock = $this->docBlockFactory->create($this->property);

        /** @var Var_ $tag */
        foreach ($docblock->getTagsByName('var') as $tag) {
            foreach ($tag->getType() as $type) {
                $types[] = (string) $this->typeResolver->resolve(ltrim($type, '\\'), $this->context);
            }
        }

        return $types;
    }
}
