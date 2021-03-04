<?php

namespace Eve\DTO;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionProperty;

abstract class DataTransferObject
{
    private array $data = [];
    private array $propertyMap = [];
    private array $exceptNames = [];
    private array $onlyNames = [];

    private function __construct(array $parameters = [])
    {
        $docblockFactory = DocBlockFactory::createInstance();
        $typeResolver = new TypeResolver();
        $context = (new ContextFactory())->createFromReflector(new ReflectionClass($this));

        foreach (static::getAssignableProperties() as $property) {
            $this->propertyMap[$property->getName()] = [
                'property' => $property,
                'validator' => new TypeValidator($docblockFactory, $typeResolver, $context, $property),
            ];

            unset($this->{$property->getName()});
        }

        $this->set($parameters);
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
            $this->{$name} = $value;
        }

        return $this;
    }

    /** @return static */
    public function unset(string ...$names): self
    {
        foreach ($names as $name) {
            $this->assertPropertyExists($name);
            unset($this->{$name});
        }

        return $this;
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        $arr = [];

        $collectablePropertyNames = array_keys($this->data);

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
        $value = $this->data[$name];

        return $value instanceof self ? $value->toArray() : $value;
    }

    /** @return array<ReflectionProperty> */
    private static function getAssignableProperties(): array
    {
        return array_filter(
            (new ReflectionClass(static::class))->getProperties(ReflectionProperty::IS_PUBLIC),
            static fn (ReflectionProperty $property): bool => !$property->isStatic()
        );
    }

    /** @return static */
    public function except(string ...$names): self
    {
        $this->exceptNames = $names;

        return $this;
    }

    /** @return static */
    public function only(string ...$names): self
    {
        $this->onlyNames = $names;

        return $this;
    }

    /** @return static */
    public function compact(): self
    {
        $this->onlyNames = [];

        foreach ($this->data as $name => $value) {
            if ($value !== null) {
                $this->onlyNames[] = $name;
            }
        }

        return $this;
    }

    private function assertPropertyExists(string $name): void
    {
        if (!array_key_exists($name, $this->propertyMap)) {
            throw DataTransferObjectException::nonexistentProperty(static::class, $name);
        }
    }

    public function __set($name, $value): void
    {
        $this->assertPropertyExists($name);

        $this->propertyMap[$name]['validator']->validate($value);
        $this->data[$name] = $value;
    }

    public function __unset($name): void
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }
    }
}
