<?php

declare(strict_types=1);

namespace CannaPress\Util\Collections;

use InvalidArgumentException;
use OutOfRangeException;

class FluentIterator implements FluentCollection
{
    private $inner;
    private $index = 0;
    private static $nil;

    public function __construct($inner)
    {
        if (\is_callable($inner)) {
            $inner = $inner();
        }
        while ($inner instanceof FluentIterator) {
            $inner = $inner->inner;
        }
        if (null === $inner) {
            throw new InvalidArgumentException('The $inner iterator is not permitted to be null');
        }
        $this->inner = is_array($inner) ? array_values($inner) : $inner;
    }

    public static function range($base, $count): FluentCollection
    {
        return new self(function () use ($base, $count) {
            for ($i = 0; $i < $count; $i = $i + 1) {
                yield $i + $base;
            }
        });
    }

    public static function from_dict(array $that): FluentCollection
    {
        return new self(function () use ($that) {
            foreach ($that as $key => $value) {
                yield (object) ['key' => $key, 'value' => $value];
            }
        });
    }

    public static function from($inner): FluentCollection
    {
        return self::to_iterable($inner);
    }

    public static function empty(): FluentCollection
    {
        return new self([]);
    }

    public function current(): mixed
    {
        if (is_array($this->inner)) {
            return current($this->inner);
        }

        return $this->inner->current();
    }

    public function key(): mixed
    {
        if (is_array($this->inner)) {
            return key($this->inner);
        } else {
            return $this->inner->key();
        }
    }

    public function next(): void
    {
        ++$this->index;
        if (is_array($this->inner)) {
            next($this->inner);
        } else {
            $this->inner->next();
        }
    }

    public function rewind(): void
    {
        $this->index = 0;
        if (is_array($this->inner)) {
            reset($this->inner);
        } else {
            $this->inner->rewind();
        }
    }

    public function valid(): bool
    {
        if (is_array($this->inner)) {
            return null !== key($this->inner);
        } else {
            return $this->inner->valid();
        }
    }

    public function append(iterable $other): FluentCollection
    {
        return new self(function () use ($other) {
            foreach ($this as $item) {
                yield $item;
            }
            foreach ($other as $item) {
                yield $item;
            }
        });
    }

    public function prepend(iterable $other): FluentCollection
    {
        return new self(function () use ($other) {
            foreach ($other as $item) {
                yield $item;
            }
            foreach ($this as $item) {
                yield $item;
            }
        });
    }

    public function filter(callable $predicate = null): FluentCollection
    {
        return new self(function () use ($predicate) {
            $predicate = $predicate ?? function ($item) {
                return !empty($item);
            };

            foreach ($this as $item) {
                if ($predicate($item, $this->index)) {
                    yield $item;
                }
            }
        });
    }

    public function map(callable $projection): FluentCollection
    {
        return new self(function () use ($projection) {
            foreach ($this as $item) {
                yield $projection($item, $this->index);
            }
        });
    }

    public function flat(): FluentCollection
    {
        return new self(function () {
            foreach ($this as $inner) {
                foreach ($inner as $item) {
                    yield $item;
                }
            }
        });
    }


    public function flat_map(callable $projection): FluentCollection
    {
        return $this->flat()->map($projection);
    }

    public function reduce(callable $reducer, $inital_value = null): mixed
    {
        $accumulator = $inital_value;
        foreach ($this as $item) {
            $accumulator = $reducer($accumulator, $item);
        }

        return $accumulator;
    }

    public function every(callable $predicate = null): bool
    {
        $predicate = $predicate ?? function ($item) {
            return !empty($item);
        };
        foreach ($this as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }
        return true;
    }

    public function some(callable $predicate = null): bool
    {
        if (is_null($predicate)) {
            $predicate = fn ($x) => true;
        }
        foreach ($this as $item) {
            if ($predicate($item)) {
                return true;
            }
        }

        return false;
    }
    public function none(callable $predicate): bool
    {
        return !$this->some($predicate);
    }

    public function includes($value, callable $equality_comparitor = null): bool
    {
        $comparitor = $comparitor ?? function ($a, $b) {
            return $a == $b;
        };

        return $this->some(function ($x) use ($comparitor, $value) {
            return $comparitor($x, $value);
        });
    }

    public function group_by($key_selector, callable $value_selector = null): FluentCollection
    {
        if (!is_callable($key_selector)) {
            $key = $key_selector;
            $key_selector = function ($x) use ($key) {
                return is_object($x) ? $x->{$key} : $x[$key];
            };
        }
        $value_selector = $value_selector ?: function ($v) {
            return $v;
        };
        if (!is_callable($value_selector)) {
            $value_key = $value_selector;
            $value_selector = function ($x) use ($value_key) {
                return is_object($x) ? $x->{$value_key} : $x[$value_key];
            };
        }
        return new self(function () use ($key_selector, $value_selector) {
            $value_selector ?? function ($x) {
                return $x;
            };
            $groupings = [];
            foreach ($this as $item) {
                $key = $key_selector($item, $this->index);
                $value = $value_selector($item, $this->index);
                if (!isset($groupings[$key])) {
                    $groupings[$key] = [];
                }
                $groupings[$key][] = $value;
            }

            foreach ($groupings as $k => $v) {
                yield (object) [
                    'key' => $k,
                    'values' => new FluentIterator($v),
                ];
            }
        });
    }

    public function sequence_equals(iterable $other, callable $comparitor): bool
    {
        $other = self::to_iterable($other);

        $this->rewind();
        $other->rewind();

        $self = $this;
        $all_valid = function () use ($self, $other) {
            return $self->valid() && $other->valid();
        };
        while ($all_valid()) {
            $cmp = $comparitor($this->current(), $other->current());
            if (0 != $cmp) {
                return false;
            }
            $this->next();
            $other->next();
        }
        //one sequence is longer
        if (($other->valid() && !$this->valid()) || ($this->valid() && !$other->valid())) {
            return false;
        }

        return true;
    }

    public function zip(iterable $other, callable $projection = null): FluentCollection
    {
        $projection = $projection ?? function ($a, $b) {
            return [$a, $b];
        };

        return new self(function () use ($other, $projection) {
            $other = self::to_iterable($other);

            $this->rewind();
            $other->rewind();

            $self = $this;
            $all_valid = function () use ($self, $other) {
                return $self->valid() && $other->valid();
            };
            while ($all_valid()) {
                yield $projection($this->current(), $other->current());
                $this->next();
                $other->next();
            }
        });
    }
    /**
     * Skips items until the predicate returns true for the first time
     * @param callable $predicate a predicate function
     * @return FluentIterattor the remaining items in the source after the predicate has returned false
     */
    public function skip_while(callable $predicate): FluentCollection
    {
        return new self(function () use ($predicate) {
            $skipping = true;
            foreach ($this as $item) {
                $skipping = $skipping ? $predicate($item, $this->index) : false;
                if (!$skipping) {
                    yield $item;
                }
            }
        });
    }

    public function page($skip, $take): FluentCollection
    {
        return $this->skip($skip)->take($take);
    }
    /**
     * Skips $count items from the source iterator
     * @param int $count the number of items to skip
     * @return FluentIterator the source
     */
    public function skip(int $count): FluentCollection
    {
        return new self(function () use ($count) {
            return $this->skip_while(function ($item, $index) use ($count) {
                return $index < $count;
            });
        });
    }
    /**
     * Takes items from the source until the predicate returns false
     * @param callable $predicate the break predicate function
     * @return FluentIterator the subset of items from the source
     */
    public function take_while(callable $predicate): FluentCollection
    {
        return new self(function () use ($predicate) {
            foreach ($this as $value) {
                if (!$predicate($value, $this->index)) {
                    goto exit_loop;
                }

                yield $value;
            }
            exit_loop:
            $i = 0;
        });
    }
    /**
     * Takes the first $count items from the source
     * @param int $count the number of items to return from the source
     * @return FluentIterator the first $count items of source
     */
    public function take(int $count): FluentCollection
    {
        return new self(function () use ($count) {
            return $this->take_while(function ($item, $index) use ($count) {
                return $index < $count;
            });
        });
    }

    public function reverse(): FluentCollection
    {
        return new self(array_reverse($this->to_array_internal()));
    }
    /** Forces the complete evaluation of all inner iterables and returns a the result
     * @return FluentIterator a fluent iterator over an array of materialized results
     */
    public function materialize()
    {
        if (is_array($this->inner)) {
            return $this;
        }
        return new self($this->to_array_internal());
    }
    /**
     * Returns the unique set of elements in the array as determined by the comparitor
     * @param callable $comparitor a comparitor function
     * @return FluentIterator the set of unique elements in this iterable
     */
    public function unique(callable $comparitor = null): FluentCollection
    {
        return new self(function () use ($comparitor) {
            $comparitor = $comparitor ?? function ($a, $b) {
                return $a - $b;
            };
            $last_value = self::nil();
            $iter = $this->order_by_asc($comparitor);
            foreach ($iter as $item) {
                if ($last_value === self::nil()) {
                    yield $item;
                    $last_value = $item;
                } else {
                    $comp = $comparitor($item, $last_value);
                    if (0 !== $comp) {
                        yield $item;
                        $last_value = $item;
                    }
                }
            }
        });
    }
    /**
     * Produces an iterable which is the union of the two sets
     * @param iterable $other the other set
     * @param callable $comparitor a comparitor function
     * @return FluentIterator the union of the two sets
     */
    public function union(iterable $other, callable $comparitor = null): FluentCollection
    {
        return $this->append($other)->unique($comparitor);
    }
    /**
     * Sorts the elements of a sequence in ascending order using the comparitor function
     * @param callable $comparitor a function which accepts two values, 'a' and 'b' returns an integer which is 0 when the values are equal, negative when a is less than b and positive when a is greater than b
     */
    public function order_by_asc(callable $comparitor = null): FluentCollection
    {
        $comparitor = $comparitor ?? function ($a, $b) {
            return $a - $b;
        };
        $arr = $this->to_array_internal();
        \usort($arr, $comparitor);

        return new self($arr);
    }



    /**
     * Sorts the elements of a sequence in descending order using the comparitor function
     * @param callable $comparitor a function which accepts two values, 'a' and 'b' returns an integer which is 0 when the values are equal, negative when a is less than b and positive when a is greater than b
     */
    public function order_by_desc(callable $comparitor = null): FluentCollection
    {
        $comparitor = $comparitor ?? function ($a, $b) {
            return $a - $b;
        };
        $comparitor = function ($a, $b) use ($comparitor) {
            return -1 * $comparitor($a, $b);
        };

        return $this->order_by_asc($comparitor);
    }
    //Produces the set difference of two sequences by using the default equality comparer to compare values.
    //public function except(iterable $other, callable $equality_comparitor){ }

    public function to_array(): IndexedCollection
    {
        return IndexedCollection::direct($this->to_array_internal());
    }

    /**
     * Materializes this fluent iterator and returns it as an array
     * @param iterable $other (optional) if this value is passed, this functions as iterator_to_array
     * @return array the contents of the iterable as an array
     */
    private function to_array_internal(iterable $other = null)
    {
        if (null == $other) {
            if (is_array($this->inner)) {
                return array_merge($this->inner);
            }

            return \iterator_to_array($this);
        }
        if ($other instanceof FluentIterator) {
            return $other->to_array();
        }

        return is_array($other) ? $other : \iterator_to_array($other);
    }
    /**
     * Produces the set intersection of two sequences
     */
    public function intersect($other): FluentCollection
    {
        return new self(function () use ($other) {
            $hash = [];
            foreach ($other as $item) {
                $hash[$item] = $item;
            }
            foreach ($this as $item) {
                if (isset($hash[$item])) {
                    yield $item;
                }
            }
        });
    }
    /**
     * Produces the difference of two sets
     */
    public function diff($other): FluentCollection
    {
        return new self(function () use ($other) {
            $hash = [];
            foreach ($other as $item) {
                $hash[$item] = $item;
            }
            foreach ($this as $item) {
                if (!isset($hash[$item])) {
                    yield $item;
                }
            }
        });
    }

    public function average(string $by = 'mean'): mixed
    {
        if ('median' == $by) {
            $arr = $this->to_array_internal();
            if (0 == count($arr)) {
                return false;
            }
            sort($arr);
            $idx = floor(count($arr) / 2);

            return $arr[$idx];
        }
        if ($by = 'mode') {
            $hist = [];
            foreach ($this as $item) {
                $hist[$item] = ($hist[$item] ?? 0) + 1;
            }
            $last = [0, 0];
            foreach ($hist as $value => $count) {
                if ($last[0] < $count) {
                    $last = [$count, $value];
                }
            }

            return $last[1];
        }
        $this->ensure_rewindable();

        return $this->sum() / $this->count();
    }
    /**
     * Returns the value of the element at a particular index
     */
    public function element_at(int $index): mixed
    {
        if (is_array($this->inner)) {
            if (isset($this->inner[$index])) {
                return $this->inner[$index];
            }
            throw new OutOfRangeException("The index $index is not valid for this FluentIterator");
        }
        $count = 0;
        foreach ($this as $item) {
            $count++;
            if ($this->index === $index) {
                return $item;
            }
        }
        if ($count > 0) {
            throw new OutOfRangeException("The index $index is not valid for this FluentIterator");
        }
        throw new OutOfRangeException("The FluentIterator contains no elements");
    }

    public function last(callable $predicate = null): mixed
    {
        if (!is_null($predicate)) {
            return $this->filter($predicate)->last();
        }
        if (is_array($this->inner)) {
            return $this->inner[\array_key_last($this->inner)];
        }
        foreach ($this as $item) {
        }

        return $item;
    }
    public function lastOrDefault(callable $predicate = null, mixed $defaultValue = null): mixed
    {
        if (!is_null($predicate)) {
            return $this->filter($predicate)->lastOrDefault(null, $defaultValue);
        }
        if (is_array($this->inner)) {
            if (count($this->inner) > 0) {
                return $this->inner[\array_key_last($this->inner)];
            }
            return $defaultValue;
        }
        $counted = 0;
        foreach ($this as $item) {
            $counted++;
        }
        if ($counted > 0) {
            return $item;
        }
        return $defaultValue;
    }

    public function first(callable $predicate = null): mixed
    {
        if (!is_null($predicate)) {
            return $this->filter($predicate)->first();
        }
        if (is_array($this->inner)) {
            if (count($this->inner) > 0) {
                return $this->inner[\array_key_first($this->inner)];
            }
            throw new OutOfRangeException("The FluentIterator contains no elements");
        }
        foreach ($this as $item) {
            return $item;
        }
        throw new OutOfRangeException("The FluentIterator contains no elements");
    }
    public function firstOrDefault(callable $predicate = null, mixed $defaultValue = null): mixed
    {
        if (!is_null($predicate)) {
            return $this->filter($predicate)->firstOrDefault(null, $defaultValue);
        }
        if (is_array($this->inner)) {
            if (count($this->inner) > 0) {
                return $this->inner[\array_key_first($this->inner)];
            }
            return $defaultValue;
        }
        foreach ($this as $item) {
            return $item;
        }
        return $defaultValue;
    }

    public function min(callable $comparitor = null): mixed
    {
        $comparitor = $comparitor ?? function ($a, $b) {
            return $a - $b;
        };
        $result = self::nil();
        foreach ($this as $item) {
            if ($result === self::nil()) {
                $result = $item;
            } else {
                if ($comparitor($item, $result) < 0) {
                    $result = $item;
                }
            }
        }
        if ($result === self::nil()) {
            return null;
        }

        return $result;
    }

    public function max(callable $comparitor = null): mixed
    {
        $comparitor = $comparitor ?? function ($a, $b) {
            return $a - $b;
        };

        $result = self::nil();
        foreach ($this as $item) {
            if ($result === self::nil()) {
                $result = $item;
            } else {
                if ($comparitor($item, $result) > 0) {
                    $result = $item;
                }
            }
        }
        if ($result === self::nil()) {
            return null;
        }

        return $result;
    }

    public function count(callable $predicate = null): int
    {
        if (!is_null($predicate)) {
            return $this->filter($predicate)->count();
        }
        return \iterator_count($this);
    }

    public function sum(callable $predicate = null): mixed
    {
        if (!is_null($predicate)) {
            return $this->filter($predicate)->sum();
        }
        $total = 0;
        foreach ($this as $item) {
            $total += $item;
        }

        return $total;
    }

    public function to_dictionary(callable $key_selector = null, callable $value_selector = null): KeyedCollection
    {
        $value_selector = $value_selector ?? function ($value, $index) {
            return $value;
        };
        $result = [];

        foreach ($this as $item) {
            $key = $key_selector($item);
            $result[$key] = $value_selector($item, $this->index);
        }

        return KeyedCollection::direct($result);
    }

    private function ensure_rewindable()
    {
        if ($this->inner instanceof \Generator || $this->inner instanceof \NoRewindIterator) {
            $arr = iterator_to_array($this->inner);
            $this->inner = $arr;
            $this->index = 0;
        }
    }

    private static function to_iterable(iterable $other)
    {
        if (!($other instanceof FluentIterator)) {
            return new self($other);
        }

        return $other;
    }

    private static function nil(): object
    {
        if (null == self::$nil) {
            self::$nil = new \stdClass();
        }

        return self::$nil;
    }
}
