<?php

declare(strict_types=1);

namespace CannaPress\Util\Collections;

interface FluentCollection extends \Iterator
{
    public function append(iterable $other): FluentCollection;
    public function prepend(iterable $other): FluentCollection;
    public function filter(callable $predicate = null): FluentCollection;
    public function map(callable $projection): FluentCollection;
    public function flat(): FluentCollection;
    public function reduce(callable $reducer, $inital_value = null): mixed;
    public function every(callable $predicate = null): bool;
    public function some(callable $predicate = null): bool;
    public function none(callable $predicate): bool;
    public function includes($value, callable $equality_comparitor = null): bool;
    public function group_by($key_selector, callable $value_selector = null): FluentCollection;
    public function sequence_equals(iterable $other, callable $comparitor): bool;
    public function zip(iterable $other, callable $projection = null): FluentCollection;
    public function skip_while(callable $predicate): FluentCollection;
    public function skip(int $count): FluentCollection;
    public function take_while(callable $predicate): FluentCollection;
    public function take(int $count): FluentCollection;
    public function reverse(): FluentCollection;
    public function unique(callable $comparitor = null): FluentCollection;
    public function union(iterable $other, callable $comparitor = null): FluentCollection;
    public function order_by_asc(callable $comparitor = null): FluentCollection;
    public function order_by_desc(callable $comparitor = null): FluentCollection;
    public function intersect($other): FluentCollection;
    public function diff($other): FluentCollection;
    public function average(string $by = 'mean'): mixed;
    public function last(callable $predicate = null): mixed;
    public function lastOrDefault(callable $predicate = null, mixed $defaultValue = null): mixed;
    public function first(callable $predicate = null): mixed;
    public function firstOrDefault(callable $predicate = null, mixed $defaultValue = null): mixed;
    public function min(callable $comparitor = null): mixed;
    public function max(callable $comparitor = null): mixed;
    public function count(callable $predicate = null): int;
    public function sum(callable $predicate = null): mixed;
    public function to_dictionary(callable $key_selector = null, callable $value_selector = null): KeyedCollection;
    public function to_array(): IndexedCollection;
}
