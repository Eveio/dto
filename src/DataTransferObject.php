<?php

namespace Eve\DTO;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionProperty;

abstract class DataTransferObject
{
    private static array $propertyMap = [];
    private array $exceptNames = [];
    private array $onlyNames = [];

    private function __construct(array $parameters = [])
    {
        $docblockFactory = DocBlockFactory::createInstance();
        $typeResolver = new TypeResolver();
        $context = (new ContextFactory())->createFromReflector(new ReflectionClass($this));

        foreach (static::getAssignableProperties() as $property) {
            self::$propertyMap[$property->name] = [
                'property' => $property,
                'validator' => new TypeValidator($docblockFactory, $typeResolver, $context, $property),
            ];
        }

        foreach ($parameters as $name => $value) {
            $this->set($name, $value);
        }
    }

    /** @return static */
    public static function make(array $parameters = []): self
    {
        return new static($parameters);
    }

    /** @param string|array $name */
    public function set($name, $value = null): self
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            if (!array_key_exists($name, static::$propertyMap)) {
                throw DataTransferObjectException::nonexistentProperty(static::class, $name);
            }

            static::$propertyMap[$name]['validator']->validate($value);
            $this->{$name} = $value;
        }

        return $this;
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        $arr = [];

        $collectablePropertyNames = array_map(
            static fn (ReflectionProperty $property): string => $property->name,
            $this->getInitializedProperties()
        );

        if ($this->onlyNames) {
            $collectablePropertyNames = array_intersect($this->onlyNames, $collectablePropertyNames);
        }

        $collectablePropertyNames = array_diff($collectablePropertyNames, $this->exceptNames);

        foreach ($collectablePropertyNames as $name) {
            $arr[$name] = $this->resolveValue($name);
        }

        return $arr;
    }

    /** @return mixed */
    private function resolveValue($name)
    {
        $value = $this->{$name};

        return $value instanceof self ? $value->toArray() : $value;
    }

    /** @return array<ReflectionProperty> */
    private function getInitializedProperties(): array
    {
        return array_filter(
            static::getAssignableProperties(),
            fn (ReflectionProperty $property): bool => $property->isInitialized($this)
        );
    }

    /** @return array<ReflectionProperty> */
    private static function getAssignableProperties(): array
    {
        return array_filter(
            (new ReflectionClass(static::class))->getProperties(ReflectionProperty::IS_PUBLIC),
            static fn (ReflectionProperty $property): bool => !$property->isStatic()
        );
    }

    /**
     * @param array<string> $names
     * @return static
     */
    public function except(...$names): self
    {
        $this->exceptNames = $names;

        return $this;
    }

    /**
     * @param array<string> $names
     * @return static
     */
    public function only(...$names): self
    {
        $this->onlyNames = $names;

        return $this;
    }

    /** @return static */
    public function compact(): self
    {
        $this->onlyNames = [];

        foreach ($this->getInitializedProperties() as $property) {
            if ($property->getValue($this) !== null) {
                $this->onlyNames[] = $property->getName();
            }
        }

        return $this;
    }
}
