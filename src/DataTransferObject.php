<?php

namespace Eve\DTO;

use ReflectionProperty;

abstract class DataTransferObject
{
    private array $data = [];
    private array $propertyNames = [];
    private array $excludedNames = [];
    private array $onlyNames = [];

    private function __construct(array $parameters = [])
    {
        foreach (static::getAssignableProperties() as $property) {
            $this->propertyNames[] = $property->getName();

            if ($property->isInitialized($this)) {
                $this->data[$property->getName()] = $property->getValue($this);
            }

            unset($this->{$property->getName()});
        }

        $this->set($parameters);
    }

    /** @return static */
    public static function make(array $parameters = []): self
    {
        return new static($parameters);
    }

    /**
     * @param string|array $name
     * @return static
     */
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

    /** @return mixed */
    public function get(string $name, $default = null)
    {
        $this->assertPropertyExists($name);

        return array_key_exists($name, $this->data) ? $this->data[$name] : $default;
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

        $collectablePropertyNames = array_diff($collectablePropertyNames, $this->excludedNames);

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
            ReflectionResolver::resolve(static::class)->getProperties(ReflectionProperty::IS_PUBLIC),
            static fn (ReflectionProperty $property): bool => !$property->isStatic()
        );
    }

    /** @return static */
    public function except(string ...$names): self
    {
        $this->assertPropertyExists(...$names);
        $this->excludedNames = $names;

        return $this;
    }

    /** @return static */
    public function only(string ...$names): self
    {
        $this->assertPropertyExists(...$names);
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

    private function assertPropertyExists(string ...$names): void
    {
        foreach ($names as $name) {
            if (!in_array($name, $this->propertyNames, true)) {
                throw DataTransferObjectException::nonexistentProperty(static::class, $name);
            }
        }
    }

    private function assertPropertyInitialized(string ...$names): void
    {
        foreach ($names as $name) {
            if (!array_key_exists($name, $this->data)) {
                throw DataTransferObjectException::propertyNotInitialized(static::class, $name);
            }
        }
    }

    public function __set($name, $value): void
    {
        $this->assertPropertyExists($name);
        $this->data[$name] = $value;
    }

    public function __unset($name): void
    {
        unset($this->data[$name]);
    }

    /** @return mixed */
    public function __get($name)
    {
        $this->assertPropertyExists($name);
        $this->assertPropertyInitialized($name);

        return $this->data[$name];
    }
}
