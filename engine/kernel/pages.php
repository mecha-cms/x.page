<?php

class Pages extends Anemone {

    protected $data = [];

    public function __get(string $key) {
        if (method_exists($this, $key) && (new ReflectionMethod($this, $key))->isPublic()) {
            return $this->{$key}();
        }
        if (parent::_($key)) {
            return $this->__call($key);
        }
        return $this->data[$key] ?? null;
    }

    public function __isset(string $key): bool {
        return null !== $this->__get($key);
    }

    public function __set(string $key, $value): void {
        if (method_exists($this, $key) && (new ReflectionMethod($this, $key))->isPublic()) {
            // Skip!
        } else if (parent::_($key)) {
            // Skip!
        } else {
            $this->data[$key] = $value;
        }
    }

    public function __serialize(): array {
        $lot = parent::__serialize();
        foreach (['lot', 'value'] as $key) {
            if (empty($lot[$key])) {
                continue;
            }
            foreach ($lot[$key] as &$v) {
                if (!is_string($v) || 0 !== strpos($v, PATH . D)) {
                    if (!is_array($v) || !$v) {
                        continue;
                    }
                    foreach ($v as &$vv) {
                        if (!is_string($vv) || 0 !== strpos($v, PATH . D)) {
                            continue;
                        }
                        $vv = strtr($vv, [PATH . D => ".\\", D => "\\"]);
                    }
                    unset($vv);
                    continue;
                }
                $v = strtr($v, [PATH . D => ".\\", D => "\\"]);
            }
            unset($v);
        }
        return $lot;
    }

    public function __toString(): string {
        return serialize($this->__serialize()['value'] ?? []);
    }

    public function __unserialize(array $lot): void {
        foreach (['lot', 'value'] as $key) {
            foreach ($lot[$key] as &$v) {
                if (!is_string($v) || 0 !== strpos($v, ".\\")) {
                    if (!is_array($v) || !$v) {
                        continue;
                    }
                    foreach ($v as &$vv) {
                        if (!is_string($vv) || 0 !== strpos($v, ".\\")) {
                            continue;
                        }
                        $vv = PATH . D . strtr(substr($vv, 2), ["\\" => D]);
                    }
                    unset($vv);
                    continue;
                }
                $v = PATH . D . strtr(substr($v, 2), ["\\" => D]);
            }
            unset($v);
        }
        parent::__unserialize($lot);
    }

    public function __unset(string $key): void {
        unset($this->data[$key]);
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

    public function getIterator(): Traversable {
        foreach ($this->value as $k => $v) {
            yield $k => is_array($v) ? $this->page(null, $v) : $this->page($v);
        }
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

    public function jsonSerialize() {
        return $this->__serialize()['value'] ?? [];
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
        if (is_callable($sort)) {
            $fn = (function ($a, $b) use ($sort) {
                return fire($sort, [is_array($a) ? $this->page(null, $a) : $this->page($a), is_array($b) ? $this->page(null, $b) : $this->page($b)], $this);
            })->bindTo($this);
            $keys ? uasort($value, $fn) : usort($value, $fn);
        } else if (is_array($sort)) {
            $lot = $value = [];
            if (isset($sort[1])) {
                foreach ($this->value as $k => $v) {
                    $f = is_array($v) ? $this->page(null, $v) : $this->page($v);
                    if (is_array($v)) {
                        $lot[$f->path] = $v;
                        $lot[$f->path]['key'] = $k;
                    }
                    if (is_string($v = $f[$sort[1]] ?? $f->{$sort[1]} ?? $sort[2] ?? null)) {
                        $v = strip_tags($v); // Ignore HTML tag(s)
                    }
                    $value[$f->path] = $v;
                }
            }
            -1 === $sort[0] ? arsort($value) : asort($value);
            $this->value = [];
            foreach (array_keys($value) as $v) {
                if (empty($lot[$v])) {
                    $this->value[] = $v;
                    continue;
                }
                $lot[$v]['path'] = $v;
                $k = $lot[$v]['key'] ?? null;
                if ($keys && isset($k)) {
                    $this->value[$k] = $lot[$v];
                } else {
                    $this->value[] = $lot[$v];
                }
            }
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
        if (is_iterable($v = reset($lot))) {
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