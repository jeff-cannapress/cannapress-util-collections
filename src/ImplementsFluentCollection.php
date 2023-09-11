<?php

declare(strict_types=1);

namespace CannaPress\Util\Collections;

trait ImplementsFluentCollection
{
    abstract protected function as_fluent() : FluentIterator;
    public function append(iterable $other): FluentCollection
    {
        return $this->as_fluent()->append($other);
    }
    public function prepend(iterable $other): FluentCollection
    {
        return $this->as_fluent()->prepend($other);
    }
    public function filter(callable $predicate = null): FluentCollection
    {
        return $this->as_fluent()->filter($predicate);
    }
    public function map(callable $projection): FluentCollection
    {
        return $this->as_fluent()->map($projection);
    }
    public function flat(): FluentCollection
    {
        return $this->as_fluent()->flat();
    }
    public function reduce(callable $reducer, $inital_value = null): mixed
    {
        return $this->as_fluent()->reduce($reducer, $inital_value);
    }
    public function every(callable $predicate = null): bool
    {
        return $this->as_fluent()->every($predicate);
    }
    public function some(callable $predicate = null): bool
    {
        return $this->as_fluent()->some($predicate);
    }
    public function none(callable $predicate): bool
    {
        return $this->as_fluent()->none($predicate);
    }
    public function includes($value, callable $equality_comparitor = null): bool
    {
        return $this->as_fluent()->includes($value, $equality_comparitor);
    }
    public function group_by($key_selector, callable $value_selector = null): FluentCollection
    {
        return $this->as_fluent()->group_by($key_selector, $value_selector);
    }
    public function sequence_equals(iterable $other, callable $comparitor): bool
    {
        return $this->as_fluent()->sequence_equals($other, $comparitor);
    }
    public function zip(iterable $other, callable $projection = null): FluentCollection
    {
        return $this->as_fluent()->zip($other, $projection);
    }
    public function skip_while(callable $predicate): FluentCollection
    {
        return $this->as_fluent()->skip_while($predicate)->to_array();
    }
    public function skip(int $count): FluentCollection
    {
        return $this->as_fluent()->skip($count);
    }
    public function take_while(callable $predicate): FluentCollection
    {
        return $this->as_fluent()->take_while($predicate);
    }
    public function take(int $count): FluentCollection
    {
        return $this->as_fluent()->take($count);
    }
    public function reverse(): FluentCollection
    {
        return $this->as_fluent()->reverse();
    }
    public function unique(callable $comparitor = null): FluentCollection
    {
        return $this->as_fluent()->unique($comparitor);
    }
    public function union(iterable $other, callable $comparitor = null): FluentCollection
    {
        return $this->as_fluent()->union($other, $comparitor);
    }
    public function order_by_asc(callable $comparitor = null): FluentCollection
    {
        return $this->as_fluent()->order_by_asc($comparitor);
    }
    public function order_by_desc(callable $comparitor = null): FluentCollection
    {
        return $this->as_fluent()->order_by_desc($comparitor);
    }
    public function intersect($other): FluentCollection
    {
        return $this->as_fluent()->intersect($other);
    }
    public function diff($other): FluentCollection
    {
        return $this->as_fluent()->diff($other);
    }
    public function average(string $by = 'mean'): mixed
    {
        return $this->as_fluent()->average($by);
    }
    public function min(callable $comparitor = null): mixed
    {
        return $this->as_fluent()->min($comparitor);
    }
    public function max(callable $comparitor = null): mixed
    {
        return $this->as_fluent()->max($comparitor);
    }

    public function sum(callable $predicate = null): mixed
    {
        return $this->as_fluent()->filter($predicate)->sum();
    }

    public function last(callable $predicate = null): mixed
    {
        return $this->as_fluent()->last($predicate);
    }
    public function lastOrDefault(callable $predicate = null, mixed $defaultValue = null): mixed
    {
        return $this->as_fluent()->lastOrDefault($predicate, $defaultValue);
    }
    public function first(callable $predicate = null): mixed
    {
        return $this->as_fluent()->first($predicate);
    }
    public function firstOrDefault(callable $predicate = null, mixed $defaultValue = null): mixed
    {
        return $this->as_fluent()->firstOrDefault($predicate, $defaultValue);
    }
}
