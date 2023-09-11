<?php

declare(strict_types=1);

namespace CannaPress\Util\Collections;


class KeyedCollection implements \ArrayAccess, FluentCollection, \JsonSerializable
{
    use ImplementsFluentCollection;
    private $inner = [];
    public static function direct(array $inner): KeyedCollection
    {
        $result = new KeyedCollection();
        $result->inner = $inner;
        return $result;
    }
    public function __construct(mixed $inner = null)
    {
        if ($inner) {
            $this->inner = self::copy_contents($inner);
        } else {
            $this->inner = [];
        }
    }
    private static function copy_contents($inner)
    {
        while (is_callable($inner)) {
            $inner = $inner();
        }
        $result = [];
        foreach ($inner as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }
    public function flip(): KeyedCollection
    {
        return self::direct(array_flip($this->inner));
    }
    public function rewind(): void
    {
        reset($this->inner);
    }
    public function current(): mixed
    {
        return current($this->inner);
    }
    public function key(): mixed
    {
        return key($this->inner);
    }
    public function next(): void
    {
        next($this->inner);
    }
    public function valid(): bool
    {
        return key($this->inner) !== null;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->inner);
    }
    public function offsetGet($offset): mixed
    {
        return array_key_exists($offset, $this->inner) ? $this->inner[$offset] : null;
    }
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->inner[] = $value;
        } else {
            $this->inner[$offset] = $value;
        }
    }
    public function offsetUnset($offset): void
    {
        unset($this->inner[$offset]);
    }
    protected function as_fluent(): FluentIterator
    {
        return new FluentIterator((function () {
            foreach ($this->inner as $key => $value) {
                yield self::make_pair($key, $value);
            }
        })());
    }
    private static function make_pair($key, $value)
    {
        return (object) new class($key, $value)
        {
            public function __construct(public $key, public $value)
            {
            }
        };
    }

    public function count(callable $predicate = null): int
    {
        if (!is_null($predicate)) {
            return $this->as_fluent()->filter($predicate)->count();
        }
        return count($this->inner);
    }
    public function has($offset)
    {
        return $this->offsetExists($offset);
    }

    public function to_dictionary(callable $key_selector = null, callable $value_selector = null): KeyedCollection
    {
        return $this->as_fluent()->to_dictionary($key_selector, $value_selector);
    }
    public function to_array(): IndexedCollection
    {
        return IndexedCollection::direct(array_values($this->inner));
    }
    public function keys(): IndexedCollection
    {
        return IndexedCollection::direct(array_keys($this->inner));
    }
    public function values(): IndexedCollection
    {
        return IndexedCollection::direct(array_values($this->inner));
    }

    public function jsonSerialize(): mixed
    {
        return $this->inner;
    }
}
