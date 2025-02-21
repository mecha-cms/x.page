<?php

class Pages extends Anemone {

    protected $data = [];
    protected $of = [];

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
        $lot = get_object_vars($this);
        unset($lot['parent']);
        if ($of = $lot['of'] ?? 0) {
            $lot['of'][0] = strtr($of[0], [PATH . D => ".\\", D => "\\"]);
            $lot['lazy'] = true;
            unset($lot['lot']);
        } else if (is_array($lot['lot'] ?? 0)) {
            foreach ($lot['lot'] as &$v) {
                $v = strtr($v, [PATH . D => ".\\", D => "\\"]);
            }
            unset($v);
        }
        return $lot;
    }

    public function __toString(): string {
        return ""; // TODO
    }

    public function __unserialize(array $lot): void {
        if ($of = $lot['of'] ?? 0) {
            $this->lazy = true;
            $this->lot = function () use ($of) {
                foreach (g(...$of) as $k => $v) {
                    if ("" !== pathinfo($k, PATHINFO_FILENAME)) {
                        yield $k;
                    }
                }
            };
            $this->of[0] = $of[0] = PATH . D . strtr(substr($of[0], 2), ["\\" => D]);
        } else if (is_array($lot['lot'] ?? 0)) {
            $this->lazy = false;
            foreach ($lot['lot'] as &$v) {
                $v = PATH . D . strtr(substr($v, 2), ["\\" => D]);
            }
            unset($v);
            $this->lot = $lot['lot'];
        }
    }

    public function __unset(string $key): void {
        unset($this->data[$key]);
    }

    //public function chunk(int $chunk = 5, int $part = -1, $keys = false) {
    //}

    public function find(callable $fn) {
        return parent::find(that(function ($v, $k) use ($fn) {
            return fire($fn, [$this->page($v), $k], $this);
        }, $this));
    }

    public function first($take = false) {
        if (null !== ($v = parent::first($take))) {
            return $this->page($v);
        }
        return $v;
    }

    public function getIterator(): Traversable {
        $lot = $this->lot;
        $lot = $this->lazy ? fire($lot) : $lot;
        foreach ($lot as $k => $v) {
            yield $k => $this->page($v);
        }
    }

    public function is(callable $fn, $keys = false) {
        return parent::is(that(function ($v, $k) use ($fn) {
            return fire($fn, [$this->page($v), $k], $this);
        }, $this), $keys);
    }

    public function jsonSerialize() {
        return ""; // TODO
    }

    public function last($take = false) {
        if (null !== ($v = parent::last($take))) {
            return $this->page($v);
        }
        return $v;
    }

    public function map(callable $fn) {
        return parent::map(that(function ($v, $k) use ($fn) {
            return fire($fn, [$this->page($v), $k], $this);
        }, $this));
    }

    public function not(callable $fn, $keys = false) {
        return parent::not(that(function ($v, $k) use ($fn) {
            return fire($fn, [$this->page($v), $k], $this);
        }, $this), $keys);
    }

    public function offsetGet($key) {
        if (null !== ($v = parent::offsetGet($key))) {
            return $this->page($v);
        }
        return $v;
    }

    public function page(...$lot) {
        if ($lot && ($v = reset($lot)) instanceof Page) {
            return $v;
        }
        return new Page(...$lot);
    }

    public function pluck(string $key, $value = null) {
        return $this->map(function ($v) use ($key, $value) {
            return $v->{$key} ?? $value;
        });
    }

    public function sort($sort = 1, $keys = false) {
        $lot = $this->lot;
        $sort = is_array($sort) ? array_replace([1, 'path', null], $sort) : (is_callable($sort) ? $sort : [$sort, 'path', null]);
        if ($this->lazy) {
            $this->lot = that(function () use ($lot, $sort) {
                $lot = fire($lot);
                foreach ($lot as $k => $v) {
                    $v = $this->page($v);
                    $value = ['path' => $v->path];
                    if ('path' !== $sort[1]) {
                        $r = $v->{f2p($sort[1])} ?? $v[$sort[1]] ?? $sort[2];
                        $value[$sort[1]] = is_object($r) && method_exists($r, '__toString') ? $r->__toString() : $r;
                    }
                    yield $k => $value;
                    unset($v);
                }
            }, $this);
        } else {
            foreach ($lot as $k => $v) {
                // TODO
            }
        }
        return parent::sort($sort, $keys);
    }

    public function vote(callable $fn, $keys = false) {
        return parent::vote(that(function ($v, $k) use ($fn) {
            return fire($fn, [$this->page($v), $k], $this);
        }, $this), $keys);
    }

    public static function from(...$lot) {
        if (is_iterable($v = reset($lot))) {
            return new static($v);
        }
        if (!is_string($v) || !is_dir($v)) {
            return new static;
        }
        $lot = array_replace([LOT . D . 'page', 'page', 0], $lot);
        $lot[0] = path($lot[0]);
        // $pages = new static(function () use ($lot) {
        //     foreach (g(...$lot) as $k => $v) {
        //         if ("" !== pathinfo($k, PATHINFO_FILENAME)) {
        //             yield $k;
        //         }
        //     }
        // });
        $pages = new static((function ($r) use ($lot) {
            foreach (g(...$lot) as $k => $v) {
                if ("" !== pathinfo($k, PATHINFO_FILENAME)) {
                    $r[] = $k;
                }
            }
            return $r;
        })([]));
        $pages->of = $lot;
        return $pages;
    }

}