<?php

class Pages extends Anemone {

    public function getIterator(): Traversable {
        foreach ($this->value as $k => $v) {
            yield $k => is_array($v) ? $this->page(null, $v) : $this->page($v);
        }
    }

    public function find($fn) {
        $fn = is_callable($fn) ? Closure::fromCallable($fn)->bindTo($this) : $fn;
        return find($this->value, function ($v, $k) {
            return call_user_func($fn, is_array($v) ? $this->page(null, $v) : $this->page($v), $k);
        });
    }

    public function first($take = false) {
        if (null !== ($first = parent::first($take))) {
            return is_array($first) ? $this->page(null, $first) : $this->page($first);
        }
        return $first;
    }

    public function is($fn, $keys = false) {
        $that = $this->mitose();
        $that->value = is($that->value, function ($v, $k) use ($fn) {
            if (!is_callable($fn) && null !== $fn) {
                $fn = function ($v) use ($fn) {
                    return $v === $fn;
                };
            }
            return fire($fn, [is_array($v) ? $this->page(null, $v) : $this->page($v), $k], $this);
        }, $keys);
        return $that;
    }

    public function last($take = false) {
        if (null !== ($last = parent::last($take))) {
            return is_array($last) ? $this->page(null, $last) : $this->page($last);
        }
        return $last;
    }

    public function map(callable $fn) {
        $that = $this->mitose();
        $that->value = map($that->value, function ($v, $k) use ($fn) {
            return fire($fn, [is_array($v) ? $this->page(null, $v) : $this->page($v), $k], $this);
        });
        return $that;
    }

    public function not($fn, $keys = false) {
        $that = $this->mitose();
        $that->value = not($that->value, function ($v, $k) use ($fn) {
            if (!is_callable($fn) && null !== $fn) {
                $fn = function ($v) use ($fn) {
                    return $v === $fn;
                };
            }
            return fire($fn, [is_array($v) ? $this->page(null, $v) : $this->page($v), $k], $this);
        }, $keys);
        return $that;
    }

    #[ReturnTypeWillChange]
    public function offsetGet($key) {
        return is_array($value = parent::offsetGet($key)) ? $this->page(null, $value) : $this->page($value);
    }

    public function page(...$lot) {
        return Page::from(...$lot);
    }

    public function pluck(string $key, $value = null, $keys = false) {
        $that = $this->mitose();
        $out = [];
        foreach ($that->value as $k => $v) {
            $v = is_array($v) ? $this->page(null, $v) : $this->page($v);
            $out[$k] = $v[$key] ?? $v->{$key} ?? $value;
        }
        $that->value = $keys ? $out : array_values($out);
        return $that;
    }

    public function sort($sort = 1, $keys = false) {
        if (count($value = $this->value) <= 1) {
            if (!$keys) {
                $this->value = array_values($this->value);
            }
            return $this;
        }
        if (is_array($sort)) {
            $value = [];
            if (isset($sort[1])) {
                foreach ($this->value as $k) {
                    $f = is_array($k) ? $this->page(null, $k) : $this->page($k);
                    if (is_string($v = $f[$sort[1]] ?? $f->{$sort[1]} ?? $sort[2] ?? null)) {
                        $v = strip_tags($v); // Ignore HTML tag(s)
                    }
                    $value[$f->path] = $v;
                }
            }
            -1 === $sort[0] ? arsort($value) : asort($value);
            $this->value = array_keys($value);
        } else {
            $value = $this->value;
            if ($keys) {
                -1 === $sort ? arsort($value) : asort($value);
            } else {
                -1 === $sort ? rsort($value) : sort($value);
            }
            $this->value = $value;
        }
        return $this;
    }

    public static function from(...$lot) {
        if (is_array($v = reset($lot))) {
            return new static($v);
        }
        $pages = [];
        foreach (g($lot[0] ?? LOT . D . 'page', $lot[1] ?? 'page', $lot[2] ?? 0) as $k => $v) {
            if ("" === pathinfo($k, PATHINFO_FILENAME)) {
                continue; // Ignore placeholder page(s)
            }
            $pages[] = $k;
        }
        return new static($pages);
    }

}