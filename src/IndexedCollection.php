<?php

declare(strict_types=1);

namespace CannaPress\Util\Collections;

class IndexedCollection implements \ArrayAccess, FluentCollection, \JsonSerializable
{
    use ImplementsFluentCollection;
    private $inner = [];
    public static function direct(array $inner): IndexedCollection
    {
        $result = new IndexedCollection();
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
        foreach ($inner as $item) {
            $result[] = $item;
        }
        return $result;
    }
    public function slice(int $offset, ?int $length): IndexedCollection
    {
        return self::direct(array_slice($this->inner, $offset, $length));
    }
    public function splice(int $offset, ?int $length = null, mixed $replacement = [])
    {
        $values = array_splice($this->inner, $offset, $length, $replacement);
        return self::direct($values);
    }
    public function push(mixed ...$items): IndexedCollection
    {
        array_push($this->inner, ...$items);
        return $this;
    }
    public function pop(): mixed
    {
        return array_pop($this->inner);
    }
    public function unshift(mixed ...$items): IndexedCollection
    {
        array_unshift($this->inner, ...$items);
        return $this;
    }
    public function shift(): mixed
    {
        return array_pop($this->inner);
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
        $offset = intval($offset);
        return array_key_exists($offset, $this->inner);
    }
    public function offsetGet(mixed $offset): mixed
    {
        $offset = intval($offset);
        return array_key_exists($offset, $this->inner) ? $this->inner[$offset] : null;
    }
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->inner[] = $value;
        } else {
            $offset = intval($offset);
            $this->inner[$offset] = $value;
        }
    }
    public function offsetUnset($offset): void
    {
        unset($this->inner[$offset]);
    }
    protected function as_fluent(): FluentIterator
    {
        return new FluentIterator($this->inner);
    }



    public function count(callable $predicate = null): int
    {
        if (!is_null($predicate)) {
            return $this->as_fluent()->filter($predicate)->count();
        }
        return count($this->inner);
    }

    public function to_dictionary(callable $key_selector = null, callable $value_selector = null): KeyedCollection
    {
        return $this->as_fluent()->to_dictionary($key_selector, $value_selector);
    }
    public function to_array(): IndexedCollection
    {
        return new IndexedCollection($this->inner);
    }
    public function jsonSerialize(): mixed
    {
        return $this->inner;
    }
}
